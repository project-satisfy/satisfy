<?php

namespace Playbloom\Service;

/**
 * Replaces credentials in a build's log with ***, wherever the log leaves
 * Satisfy, whether kept for a diagnosis, sent to a model or returned over MCP:
 * user information in URLs, tokens with a known prefix, authentication
 * headers, and the credential sections of COMPOSER_AUTH or an auth.json.
 * Package names, hosts and paths are left as they are.
 */
final class LogRedactor
{
    private const PATTERNS = [
        // The objects of an auth.json or COMPOSER_AUTH, keyed by host.
        '~("(?:github-oauth|gitlab-oauth|gitlab-token|gitlab-domains-token|bitbucket-oauth|http-basic|bearer|forgejo-token)"\s*:\s*)\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}~i' => '$1{"***": "***"}',
        // Single values in JSON.
        '~("(?:password|token|secret|access[_-]?token|private[_-]?token|consumer-secret)"\s*:\s*)"[^"]*"~i' => '$1"***"',
        // https://token@host/ and https://user:password@host/.
        '~\b([a-z][a-z0-9+.-]*://)[^/\s@"\']+@~i' => '$1***@',
        // GitHub and GitLab tokens, which carry a prefix.
        '~\b(gh[pousr]_|github_pat_|glpat-|glcbt-|gldt-|glptt-)[A-Za-z0-9_-]{8,}~' => '$1***',
        // Authentication headers and settings.
        '~\b(Authorization|PRIVATE-TOKEN|X-Gitlab-Token|JOB-TOKEN)(\s*[:=]\s*)(Bearer\s+|Basic\s+|token\s+)?[^\s"\']+~i' => '$1$2$3***',
        '~\b(COMPOSER_AUTH=)\S+~' => '$1***',
    ];

    public static function redact(string $text): string
    {
        return (string) preg_replace(array_keys(self::PATTERNS), array_values(self::PATTERNS), $text);
    }
}
