<?php

namespace Playbloom\Plugin\Authz;

/**
 * Answers nginx's auth_request subrequest for one catalogue request. Only the
 * status matters to nginx: 2xx allows, 401 and 403 refuse.
 */
final class Verifier
{
    public function __construct(
        private readonly AuthzConfig $config,
        private readonly Token $token,
        private readonly Entitlement $entitlement,
        private readonly Audit $audit,
    ) {
    }

    /**
     * Built by hand, for public/verify.php, which runs without the kernel.
     */
    public static function create(AuthzConfig $config): self
    {
        $audit = new Audit($config);
        $database = new Database($config);

        return new self(
            $config,
            new Token($config, $database, $audit),
            new Entitlement($database, new Deps($config), $audit),
            $audit
        );
    }

    /**
     * @param string $header the request's Authorization header
     * @param string $uri    the original request URI
     *
     * @return array{0: int, 1: array<string, string>} status, and headers nginx can log
     */
    public function decide(string $header, string $uri, string $ip): array
    {
        // Read as nginx read it to find the file: the URI is $request_uri, as sent.
        $path = Uri::path($uri);
        $canary = AuthzConfig::CANARY_PREFIX === $path;
        $enforce = $canary || $this->config->isEnforcing();

        $site = $this->token->verify($header);
        $authorized = null !== $site;

        // Permissive mode records and allows, so real consumers are known
        // before anything is refused.
        $allow = $authorized || !$enforce;
        $status = $allow ? 200 : 401;
        $reason = $authorized ? null : 'credential';

        $package = null;
        $packageOutcome = null;
        if ($authorized && null === $path && $enforce) {
            // Composer never sends a path that cannot be read for certain, and
            // the package it names could not be checked.
            $allow = false;
            $status = 403;
            $reason = 'uri';
        } elseif ($authorized && !$canary) {
            $package = Uri::package($uri);
            if (null !== $package) {
                $packageOutcome = $this->entitlement->check($site, $package);
                if (Entitlement::DENY === $packageOutcome && $enforce) {
                    // 403, not 401: a 401 makes Composer retry the same credential for ever.
                    $allow = false;
                    $status = 403;
                    $reason = 'package';
                }
            }
        }

        // Successful canary probes are not logged; they arrive on a timer.
        if (!$canary || !$allow) {
            $this->audit->write([
                'site' => $site,
                'uri' => $uri,
                'ip' => $ip,
                'ok' => $authorized,
                'allowed' => $allow,
                'enforced' => $enforce,
                'canary' => $canary,
                'had_cred' => '' !== $header,
                'package' => $package,
                'package_outcome' => $packageOutcome,
                'reason' => $reason,
            ]);
        }

        $headers = [
            'X-Authz-Site' => $site ?? 'unauthenticated',
            'X-Authz-Enforced' => $enforce ? 'true' : 'false',
        ];
        if (null !== $package) {
            $headers['X-Authz-Package'] = $package;
        }

        return [$status, $headers];
    }
}
