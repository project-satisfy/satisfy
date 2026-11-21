<?php

namespace Playbloom\Plugin\Mcp;

use Mcp\Exception\ToolCallException;
use Playbloom\Plugin\Mcp\Token\TokenStore;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Which token is calling, for the tools that change Satisfy: they refuse a
 * token issued to read, and each change they make is recorded with the
 * token's name in audit.jsonl beside the tokens. A file of its own rather than
 * Satisfy's log, which keeps only errors by default.
 */
final class Access
{
    public const ATTRIBUTE = '_satisfy_mcp_token';

    public function __construct(
        private readonly RequestStack $requests,
        private readonly TokenStore $tokens,
    ) {
    }

    /**
     * @return array{name: string, scope: string}|null
     */
    public function token(): ?array
    {
        $token = $this->requests->getMainRequest()?->attributes->get(self::ATTRIBUTE);

        return is_array($token) ? $token : null;
    }

    /**
     * @throws ToolCallException for a token that may only read
     */
    public function requireWrite(): void
    {
        if (TokenStore::WRITE !== ($this->token()['scope'] ?? null)) {
            throw new ToolCallException('This token may only read. A token issued with write scope, by bin/console satisfy:mcp-token issue <name> --write or at /admin/mcp-tokens, can change Satisfy.');
        }
    }

    /**
     * @param array<string, mixed> $change
     */
    public function record(string $tool, array $change): void
    {
        $line = json_encode([
            'ts' => time(),
            'token' => $this->token()['name'] ?? null,
            'tool' => $tool,
            'change' => $change,
        ], \JSON_UNESCAPED_SLASHES) . "\n";
        @file_put_contents($this->tokens->getDir() . '/audit.jsonl', $line, \FILE_APPEND | \LOCK_EX);
    }
}
