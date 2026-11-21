<?php

namespace Playbloom\Plugin\Mcp\Tool;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;
use Playbloom\Service\Manager;

/**
 * satis.json's require list: the packages it publishes, with constraints.
 */
final class Requirements
{
    public function __construct(private readonly Manager $manager)
    {
    }

    /**
     * @return array{require_all: bool, require: array<string, string>}
     */
    #[McpTool(
        name: 'requirements',
        description: 'The packages satis.json asks Satis to publish, each with its version constraint, and whether require-all is on, in which case every package in the repositories is published and the list is not used.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function __invoke(
        #[Schema(description: 'Only the packages whose name contains this text; empty for all.')]
        string $contains = '',
    ): array {
        $config = $this->manager->getConfig();
        $require = [];
        foreach ($config->getRequire() ?? [] as $constraint) {
            if ('' === $contains || false !== stripos($constraint->getPackage(), $contains)) {
                $require[$constraint->getPackage()] = $constraint->getConstraint();
            }
        }

        return ['require_all' => $config->isRequireAll(), 'require' => $require];
    }
}
