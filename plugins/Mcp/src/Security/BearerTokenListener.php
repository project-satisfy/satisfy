<?php

namespace Playbloom\Plugin\Mcp\Security;

use Playbloom\Plugin\Mcp\Access;
use Playbloom\Plugin\Mcp\McpServerBundle;
use Playbloom\Plugin\Mcp\Token\TokenStore;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Answers 401 to a request for the MCP endpoint unless its Authorization
 * header carries one of the tokens in TokenStore, and records which token it
 * was and its scope on the request, for the tools that write. While no token
 * has been issued, every request is refused.
 *
 * A listener rather than a firewall: Symfony takes firewalls from one
 * configuration file only, which is Satisfy's, and its firewall signs in by
 * form. It runs after routing and before that firewall.
 */
final class BearerTokenListener
{
    public function __construct(private readonly TokenStore $tokens)
    {
    }

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 9)]
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();
        if (!$event->isMainRequest() || (McpServerBundle::PATH !== $path && !str_starts_with($path, McpServerBundle::PATH . '/'))) {
            return;
        }

        $header = (string) $request->headers->get('Authorization');
        $token = 1 === preg_match('/^Bearer\s+(\S+)$/i', $header, $match) ? $this->tokens->find($match[1]) : null;
        if (null !== $token) {
            $request->attributes->set(Access::ATTRIBUTE, $token);
            $this->tokens->touch($token['name']);

            return;
        }

        $event->setResponse(new JsonResponse(
            ['error' => 'A bearer token is required: one issued by bin/console satisfy:mcp-token or at /admin/mcp-tokens.'],
            Response::HTTP_UNAUTHORIZED,
            ['WWW-Authenticate' => 'Bearer realm="Satisfy MCP"'],
        ));
    }
}
