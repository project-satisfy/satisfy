<?php

namespace Playbloom\Satisfy\Providers;

use JMS\Serializer\Context;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\SerializerBuilder;
use PhpCollection\Map;
use Playbloom\Satisfy\Models\FilePersister;
use Playbloom\Satisfy\Models\JsonPersister;
use Playbloom\Satisfy\Models\LockProcessor;
use Playbloom\Satisfy\Models\Manager;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Filesystem\Filesystem;

class SatisServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param  \Silex\Application  $app
     */
    public function register(Application $app)
    {
        $app['satis'] = $app->share(function () use ($app) {
            $filesystem = new Filesystem();

            $serializer = SerializerBuilder::create()
                ->configureHandlers(function (HandlerRegistry $registry) use ($app) {
                    $registry->registerHandler('serialization', 'RepositoryCollection', 'json',
                        function ($visitor, Map $map, array $type, Context $context) use ($app) {
                            // We change the base type, and pass through possible parameters.
                            $type['name'] = 'array';
                            $data = $map->values();

                            if (! empty($app['config']['satis']['file_formatting'])) {
                                $visitor->setOptions(JSON_PRETTY_PRINT);
                            }

                            return $visitor->visitArray($data, $type, $context);
                        }
                    );
                })
                ->configureHandlers(function (HandlerRegistry $registry) {
                    $registry->registerHandler('deserialization', 'RepositoryCollection', 'json',
                        function ($visitor, array $data, array $type, Context $context) {

                            // We change the base type, and pass through possible parameters.
                            $type['name'] = 'array';

                            $objects = $visitor->visitArray($data, $type, $context);
                            $map = new Map();

                            foreach ($objects as $object) {
                                $map->set($object->getId(), $object);
                            }

                            return $map;
                        }
                    );
                })
                ->build();

            $filePersister = new FilePersister(
                $filesystem,
                storage_path($app['config']['satis']['filename']),
                storage_path('recovery')
            );
            $jsonPersister = new JsonPersister(
                $filePersister,
                $serializer,
                $app['config']['satis']['class']
            );

            return new Manager($jsonPersister);
        });

        $app['satis.lock'] = $app->share(function () use ($app) {
            return new LockProcessor($app['satis']);
        });
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     *
     * @param  \Silex\Application  $app
     */
    public function boot(Application $app)
    {
        // ...
    }
}
