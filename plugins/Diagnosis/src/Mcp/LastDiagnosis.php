<?php

namespace Playbloom\Plugin\Diagnosis\Mcp;

use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\ToolAnnotations;
use Playbloom\Plugin\Diagnosis\DiagnosisStore;

/**
 * The newest diagnosis, for the mcp plugin's server: as a resource, and as a
 * tool for clients that call tools more readily than they read resources.
 */
final class LastDiagnosis
{
    private const DESCRIPTION = 'The diagnosis of the newest failed build: the build (id, exit code, what it built, when it finished), the status of the diagnosis (done or failed), the model\'s answer, and the model. {"status": "none"} until a build has failed.';

    public function __construct(private readonly DiagnosisStore $store)
    {
    }

    #[McpResource(
        uri: 'satisfy://build/diagnosis',
        name: 'build_diagnosis',
        description: self::DESCRIPTION,
        mimeType: 'application/json',
    )]
    public function __invoke(): string
    {
        return json_encode($this->last(), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'last_diagnosis',
        description: self::DESCRIPTION . ' A build is diagnosed a few seconds after it fails.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function last(): array
    {
        return $this->store->last() ?? ['status' => 'none'];
    }
}
