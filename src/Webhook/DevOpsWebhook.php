<?php

namespace Playbloom\Webhook;

use Playbloom\Model\RepositoryInterface;
use Playbloom\Service\Manager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class DevOpsWebhook extends AbstractWebhook
{
    private const HTTP_TOKEN = 'X-DEVOPS-TOKEN';

    public function __construct(
        Manager $manager,
        EventDispatcherInterface $dispatcher,
        ?string $secret = null,
    ) {
        parent::__construct($manager, $dispatcher);
        $this->manager = $manager;
        $this->dispatcher = $dispatcher;
        $this->secret = $secret;
    }

    /**
     * {@inheritDoc}
     */
    protected function validate(Request $request): void
    {
        // The header is one the service hook is set up to send, under "HTTP headers".
        $token = $request->headers->get(self::HTTP_TOKEN);
        $matches = null === $token || null === $this->secret
            ? $token === $this->secret
            : hash_equals($this->secret, $token);
        if (!$matches) {
            throw new \InvalidArgumentException('Invalid Token');
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getRepository(Request $request): RepositoryInterface
    {
        $repository = $this->getPayload($request)['resource']['repository'] ?? null;
        if (!is_array($repository)) {
            throw new \InvalidArgumentException('Invalid Request');
        }

        // remoteUrl is the clone URL; url is the repository's REST API URL,
        // kept for deliveries that put a clone URL there.
        $patterns = [];
        foreach (['remoteUrl', 'url'] as $key) {
            $url = $repository[$key] ?? null;
            if (!is_string($url) || !str_starts_with($url, 'https://')) {
                continue;
            }
            $path = (string) parse_url($url, PHP_URL_PATH);
            if ('' === $path || '/' === $path) {
                continue;
            }
            // Matched by path, since a clone URL may carry a user name.
            $patterns[] = '#' . preg_quote($path, '#') . '$#i';
            if (preg_match('#^/([^/]+)/([^/]+)/_git/([^/]+)$#', $path, $parts)) {
                $patterns[] = '#[:/]v3/' . preg_quote("{$parts[1]}/{$parts[2]}/{$parts[3]}", '#') . '$#i';
            }
        }
        if (is_string($repository['sshUrl'] ?? null) && '' !== $repository['sshUrl']) {
            $patterns[] = '#^' . preg_quote($repository['sshUrl'], '#') . '$#i';
        }
        if ([] === $patterns) {
            throw new \InvalidArgumentException('Invalid Request');
        }

        $found = $this->findRepository($patterns);
        if (!$found instanceof RepositoryInterface) {
            throw new \InvalidArgumentException('Invalid Repository');
        }

        return $found;
    }
}
