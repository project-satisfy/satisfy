<?php

namespace Playbloom\Plugin\Mcp\Controller;

use Playbloom\Controller\AbstractProtectedController;
use Playbloom\Plugin\Mcp\McpServerBundle;
use Playbloom\Plugin\Mcp\Token\TokenStore;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The MCP server's tokens in the admin UI: the same ones the
 * satisfy:mcp-token command manages. A token just issued is shown on the next
 * page view only.
 */
class TokenController extends AbstractProtectedController
{
    private const CSRF = 'mcp-tokens';

    public function __construct(private readonly TokenStore $tokens)
    {
    }

    #[Route('/admin/mcp-tokens', name: 'mcp_tokens', methods: ['GET'])]
    public function indexAction(Request $request): Response
    {
        $this->checkAccess();
        $issued = $request->getSession()->remove('mcp_token_issued');

        return $this->render('@McpServer/tokens.html.twig', [
            'tokens' => $this->tokens->all(),
            'issued' => is_array($issued) ? $issued : null,
            'endpoint' => $request->getSchemeAndHttpHost() . McpServerBundle::PATH,
            'name_pattern' => TokenStore::NAME,
            'csrf' => self::CSRF,
        ]);
    }

    #[Route('/admin/mcp-tokens', name: 'mcp_tokens_action', methods: ['POST'])]
    public function actionAction(Request $request): Response
    {
        $this->checkAccess();
        if (!$this->isCsrfTokenValid(self::CSRF, (string) $request->request->get('_token'))) {
            $this->addFlash('warning', 'The form had expired. Reload the page and try again.');

            return $this->redirectToRoute('mcp_tokens');
        }

        $name = trim((string) $request->request->get('name'));
        try {
            switch ($request->request->get('action')) {
                case 'issue':
                    $scope = TokenStore::WRITE === $request->request->get('scope') ? TokenStore::WRITE : TokenStore::READ;
                    $token = $this->tokens->issue($name, $scope);
                    $request->getSession()->set('mcp_token_issued', ['name' => $name, 'scope' => $scope, 'token' => $token]);
                    break;

                case 'revoke':
                    if ($this->tokens->revoke($name)) {
                        $this->addFlash('success', sprintf('Revoked %s. A client sending it is refused from now on.', $name));
                    } else {
                        $this->addFlash('warning', sprintf('No token is named %s.', $name));
                    }
                    break;

                default:
                    $this->addFlash('warning', 'Unknown action.');
            }
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            $this->addFlash('warning', 'Failed: ' . $exception->getMessage());
        }

        return $this->redirectToRoute('mcp_tokens');
    }
}
