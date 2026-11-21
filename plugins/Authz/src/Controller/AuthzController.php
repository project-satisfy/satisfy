<?php

namespace Playbloom\Plugin\Authz\Controller;

use Playbloom\Controller\AbstractProtectedController;
use Playbloom\Plugin\Authz\Audit;
use Playbloom\Plugin\Authz\AuthzConfig;
use Playbloom\Plugin\Authz\Cache;
use Playbloom\Plugin\Authz\Database;
use Playbloom\Plugin\Authz\Deps;
use Playbloom\Plugin\Authz\Entitlement;
use Playbloom\Plugin\Authz\Sites;
use Playbloom\Service\Manager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Sites and grants in the admin UI: an overview, and a page per site where
 * every change is made. Every change redirects, so reloading a page never
 * repeats one; that matters most for replacing a token.
 *
 * Changes clear authz's cache at once, since this runs in the same PHP-FPM
 * pool as the verification endpoint.
 */
class AuthzController extends AbstractProtectedController
{
    private const CSRF = 'authz';

    public function __construct(
        private readonly AuthzConfig $config,
        private readonly Database $database,
        private readonly Sites $sites,
        private readonly Entitlement $entitlement,
        private readonly Deps $deps,
        private readonly Audit $audit,
    ) {
    }

    #[Route('/admin/authz', name: 'authz', methods: ['GET'])]
    public function indexAction(): Response
    {
        $this->checkAccess();

        $db = $this->database->writable();
        $requests = [];
        foreach ($this->entitlement->openRequests($db) as $row) {
            $requests[$row['site']][] = $row;
        }
        $db->close();
        $summary = $this->audit->summary();

        return $this->render('@Authz/index.html.twig', [
            'sites' => $this->sites->all(),
            'requests' => $requests,
            'observed' => $summary['observed'],
            'unauth' => $summary['unauth'],
            'enforcing' => $this->config->isEnforcing(),
            'csrf' => self::CSRF,
        ]);
    }

    #[Route('/admin/authz/site/{site}', name: 'authz_site', requirements: ['site' => '[A-Za-z0-9._-]{1,64}'], methods: ['GET'])]
    public function siteAction(Request $request, string $site): Response
    {
        $this->checkAccess();

        $row = $this->sites->find($site);
        if (null === $row) {
            $this->addFlash('warning', 'No such site: ' . $site);

            return $this->redirectToRoute('authz');
        }

        $db = $this->database->writable();
        $direct = $this->entitlement->granted($db, $site);
        $closure = $this->entitlement->effective($db, $site);
        $requests = $this->entitlement->openRequests($db, $site);
        $db->close();
        $implied = array_values(array_diff(array_keys($closure), array_map('strtolower', $direct)));
        sort($implied);

        // A token just issued, shown on this one page view and then gone.
        $issued = $request->getSession()->remove('authz_issued');
        if (!is_array($issued) || ($issued['site'] ?? null) !== $site) {
            $issued = null;
        }

        return $this->render('@Authz/site.html.twig', [
            'site' => $site,
            'row' => $row,
            'direct' => $direct,
            'implied' => $implied,
            'reach' => count($closure),
            'requests' => $requests,
            'hosted' => $this->deps->hosted(),
            'issued' => $issued,
            'composer_auth' => null === $issued ? null : $this->composerAuth($site, (string) $issued['token']),
            'seen' => $this->audit->summary()['observed'][$site] ?? null,
            'csrf' => self::CSRF,
        ]);
    }

    #[Route('/admin/authz', name: 'authz_action', methods: ['POST'])]
    public function actionAction(Request $request): RedirectResponse
    {
        $this->checkAccess();

        if (!$this->isCsrfTokenValid(self::CSRF, (string) $request->request->get('_token'))) {
            $this->addFlash('warning', 'The form had expired. Reload the page and try again.');

            return $this->redirectToRoute('authz');
        }

        $action = (string) $request->request->get('action');
        $site = trim((string) $request->request->get('site'));
        $package = strtolower(trim((string) $request->request->get('package')));
        $by = $this->getUser()?->getUserIdentifier() ?? 'admin-ui';
        $back = 'index' === $request->request->get('back') || '' === $site
            ? $this->redirectToRoute('authz')
            : $this->redirectToRoute('authz_site', ['site' => $site]);

        try {
            switch ($action) {
                case 'issue':
                    $rotated = $this->sites->exists($site);
                    $token = $this->sites->issue($site, $rotated ? null : (string) $request->request->get('note'));
                    Cache::clear();
                    $this->audit->write(['event' => $rotated ? 'reissue' : 'issue', 'site' => $site, 'by' => $by]);
                    $request->getSession()->set('authz_issued', ['site' => $site, 'token' => $token, 'rotated' => $rotated]);

                    return $this->redirectToRoute('authz_site', ['site' => $site]);

                case 'note':
                    $this->sites->setNote($site, trim((string) $request->request->get('note')));
                    $this->addFlash('success', 'Note saved.');
                    break;

                case 'revoke':
                case 'restore':
                    $this->sites->setActive($site, 'restore' === $action);
                    Cache::clear();
                    $this->audit->write(['event' => $action, 'site' => $site, 'by' => $by]);
                    $this->addFlash('success', sprintf('%s %s.', 'restore' === $action ? 'Restored' : 'Revoked', $site));
                    break;

                case 'enforce':
                    $on = 'on' === $request->request->get('value');
                    $this->config->setEnforcing($on);
                    $this->audit->write(['event' => 'enforce', 'value' => $on ? 'on' : 'off', 'by' => $by]);
                    $this->addFlash('success', $on
                        ? 'Credential enforcement is on: requests without a valid token are refused.'
                        : 'Credential enforcement is off: requests are recorded and allowed.');
                    break;

                case 'pkgmode':
                    $on = 'on' === $request->request->get('value');
                    $db = $this->database->writable();
                    $this->entitlement->setEnforce($db, $site, $on);
                    $reach = count($this->entitlement->effective($db, $site));
                    $db->close();
                    Cache::clear();
                    $this->audit->write(['event' => 'pkgmode', 'site' => $site, 'value' => $on ? 'on' : 'off', 'by' => $by]);
                    $this->addFlash('success', $on
                        ? sprintf('Package enforcement on. %s can reach %d package(s); any other is refused with 403.', $site, $reach)
                        : 'Package enforcement off. Packages not granted are allowed, and still recorded.');
                    break;

                case 'approve_all':
                    $db = $this->database->writable();
                    $open = $this->entitlement->openRequests($db, $site);
                    foreach ($open as $row) {
                        $this->entitlement->grant($db, $site, (string) $row['package'], $by);
                    }
                    $reach = count($this->entitlement->effective($db, $site));
                    $db->close();
                    Cache::clear();
                    $this->audit->write(['event' => 'approve_all', 'site' => $site, 'count' => count($open), 'by' => $by]);
                    $this->addFlash('success', sprintf('Approved %d package(s) for %s, which can now reach %d, dependencies included.', count($open), $site, $reach));
                    break;

                case 'grant':
                case 'approve':
                    if ('' === $package) {
                        throw new \InvalidArgumentException('No package given.');
                    }
                    $db = $this->database->writable();
                    $this->entitlement->grant($db, $site, $package, $by);
                    $reach = count($this->entitlement->effective($db, $site));
                    $db->close();
                    Cache::clear();
                    $this->audit->write(['event' => $action, 'site' => $site, 'package' => $package, 'by' => $by]);
                    $this->addFlash('success', sprintf('Granted %s to %s, which can now reach %d package(s), dependencies included.', $package, $site, $reach));
                    break;

                case 'ungrant':
                    $db = $this->database->writable();
                    $removed = $this->entitlement->revoke($db, $site, $package);
                    $db->close();
                    Cache::clear();
                    $this->audit->write(['event' => 'ungrant', 'site' => $site, 'package' => $package, 'by' => $by]);
                    $this->addFlash('success', $removed > 0 ? sprintf('Removed %s from %s.', $package, $site) : sprintf('%s was not granted %s.', $site, $package));
                    break;

                case 'dismiss':
                    $db = $this->database->writable();
                    $this->entitlement->dismiss($db, $site, $package);
                    $db->close();
                    $this->audit->write(['event' => 'dismiss', 'site' => $site, 'package' => $package, 'by' => $by]);
                    $this->addFlash('success', sprintf('Dismissed %s for %s without granting it.', $package, $site));
                    break;

                default:
                    $this->addFlash('warning', 'Unknown action.');
            }
        } catch (\Throwable $exception) {
            $this->addFlash('warning', 'Failed: ' . $exception->getMessage());
        }

        return $back;
    }

    /**
     * COMPOSER_AUTH for a site, which is also the whole of its auth.json.
     */
    private function composerAuth(string $site, string $token): string
    {
        $host = parse_url((string) $this->container->get(Manager::class)->getConfig()->getHomepage(), PHP_URL_HOST);

        return (string) json_encode(
            ['http-basic' => [is_string($host) && '' !== $host ? $host : 'localhost' => ['username' => $site, 'password' => $token]]],
            JSON_UNESCAPED_SLASHES
        );
    }
}
