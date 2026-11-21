<?php

namespace Playbloom\Plugin\Mcp\Tool;

use Composer\Semver\VersionParser;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Playbloom\Model\Configuration;
use Playbloom\Model\PackageConstraint;
use Playbloom\Plugin\Mcp\Access;
use Playbloom\Service\Manager;

/**
 * Adds a package to satis.json's require list, or changes its constraint.
 */
final class RequirePackage
{
    public const NAME = '~^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$~';

    public function __construct(
        private readonly Access $access,
        private readonly Manager $manager,
    ) {
    }

    /**
     * @return array{package: string, from: string|null, to: string}
     */
    #[McpTool(
        name: 'require_package',
        description: 'Adds a package to satis.json\'s require list with a version constraint, or replaces the constraint it has. The constraint is checked as Composer reads one. The package is published by the next full build (queue_build), provided a repository in satis.json holds it. Needs a write token.',
        annotations: new ToolAnnotations(readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false),
    )]
    public function __invoke(
        #[Schema(description: 'The package name, such as psr/log.')]
        string $package,
        #[Schema(description: 'A Composer version constraint, such as ^3.0 || ^2.0, or * for every version.')]
        string $constraint,
    ): array {
        $this->access->requireWrite();
        $package = strtolower(trim($package));
        $constraint = trim($constraint);
        if (1 !== preg_match(self::NAME, $package)) {
            throw new ToolCallException(sprintf('"%s" is not a Composer package name, vendor/name in lowercase.', $package));
        }
        try {
            (new VersionParser())->parseConstraints($constraint);
        } catch (\UnexpectedValueException $exception) {
            throw new ToolCallException(sprintf('Composer cannot read the constraint "%s": %s', $constraint, $exception->getMessage()));
        }
        if ($this->manager->getConfig()->isRequireAll()) {
            throw new ToolCallException('require-all is on in satis.json, so every package in the repositories is published and the require list is not used.');
        }

        $from = null;
        $this->manager->modify(static function (Configuration $config) use ($package, $constraint, &$from): void {
            $require = [];
            foreach ($config->getRequire() ?? [] as $existing) {
                if ($existing->getPackage() === $package) {
                    $from = $existing->getConstraint();
                    $existing = new PackageConstraint($package, $constraint);
                }
                $require[] = $existing;
            }
            if (null === $from) {
                $require[] = new PackageConstraint($package, $constraint);
            }
            $config->setRequire($require);
        });

        $change = ['package' => $package, 'from' => $from, 'to' => $constraint];
        $this->access->record('require_package', $change);

        return $change;
    }
}
