<?php

namespace Playbloom\Plugin\Authz;

/**
 * Maps a catalogue request path to the package it concerns.
 */
final class Uri
{
    /**
     * The path of a request URI as nginx resolves it to a file: without the
     * query, percent-decoded, repeated slashes merged and dot segments
     * removed. nginx passes $request_uri, which is none of these, and
     * /p2/x/../vendor/name.json or //p2/vendor/name.json is still the file
     * /p2/vendor/name.json.
     *
     * Null when the path cannot be read as nginx reads it for certain: a "#",
     * an encoded "?", a backslash or a NUL, or ".." above the root. No
     * catalogue path has any of them.
     */
    public static function path(string $uri): ?string
    {
        $path = rawurldecode(explode('?', $uri, 2)[0]);
        if (!str_starts_with($path, '/') || false !== strpbrk($path, "?#\\\0")) {
            return null;
        }

        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ('' === $segment || '.' === $segment) {
                continue;
            }
            if ('..' === $segment) {
                if ([] === $segments) {
                    return null;
                }
                array_pop($segments);
                continue;
            }
            $segments[] = $segment;
        }
        $normalized = '/' . implode('/', $segments);

        return [] !== $segments && str_ends_with($path, '/') ? $normalized . '/' : $normalized;
    }

    /**
     * The package a request URI is for, or null for whole-catalogue documents
     * (/, /index.html, /packages.json, /include/*, Composer 1's
     * /p/provider-*.json), which are gated on the credential alone, and for a
     * path() cannot read.
     *
     * Reads path segments, never the archive's file name: versions contain
     * dashes, and Satis also writes commit-reference names.
     */
    public static function package(string $uri): ?string
    {
        $path = self::path($uri);
        if (null === $path) {
            return null;
        }

        // /p2/vendor/name.json and /p2/vendor/name~dev.json
        if (1 === preg_match('#^/p2/([^/]+)/([^/]+?)(~dev)?\.json$#', $path, $matches)) {
            return strtolower($matches[1] . '/' . $matches[2]);
        }
        // /dist/vendor/name/<anything>
        if (1 === preg_match('#^/dist/([^/]+)/([^/]+)/#', $path, $matches)) {
            return strtolower($matches[1] . '/' . $matches[2]);
        }
        // Composer 1's provider files, which Satis writes with "providers": /p/vendor/name$<sha256>.json
        if (1 === preg_match('#^/p/([^/]+)/([^/$]+)(\$[0-9a-f]*)?\.json$#', $path, $matches)) {
            return strtolower($matches[1] . '/' . $matches[2]);
        }

        return null;
    }
}
