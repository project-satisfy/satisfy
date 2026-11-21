<?php

namespace Playbloom\Plugin;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * A Satisfy plugin: a bundle laid out as plugins/<Name>/{src,config,templates}.
 * While it is enabled its config/services.yaml is loaded, the controllers in
 * src/Controller/ get their routes, and so do the imports in config/routes.yaml;
 * its templates are @<Name>/....
 */
abstract class AbstractPlugin extends AbstractBundle
{
    /**
     * Bundles enabled with the plugin, ahead of it, such as one it configures.
     *
     * @return class-string<BundleInterface>[]
     */
    public static function requiredBundles(): array
    {
        return [];
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $this->getPath() . '/config/services.yaml';
        if (is_file($services)) {
            $container->import($services);
        }
    }

    /**
     * The directory of the plugin's controllers, if it has any.
     */
    public function getControllerPath(): ?string
    {
        $path = $this->getPath() . '/src/Controller';

        return is_dir($path) ? $path : null;
    }

    /**
     * The plugin's route imports, if it has any.
     */
    public function getRoutesPath(): ?string
    {
        $path = $this->getPath() . '/config/routes.yaml';

        return is_file($path) ? $path : null;
    }
}
