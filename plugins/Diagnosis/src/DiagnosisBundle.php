<?php

namespace Playbloom\Plugin\Diagnosis;

use Playbloom\Plugin\AbstractPlugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * When a build fails, asks a model why, through Symfony AI's agent, with the
 * build's log redacted and without COMPOSER_AUTH, and keeps the answer. With
 * the mcp plugin enabled too, the newest answer is its server's resource
 * satisfy://build/diagnosis.
 */
class DiagnosisBundle extends AbstractPlugin
{
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if ($builder->hasExtension('mcp')) {
            $builder->prependExtensionConfig('mcp', [
                'servers' => [
                    'satisfy' => ['registry' => ['tools' => [__NAMESPACE__ . '\\Mcp\\'], 'resources' => [__NAMESPACE__ . '\\Mcp\\']]],
                ],
            ]);
        }
    }
}
