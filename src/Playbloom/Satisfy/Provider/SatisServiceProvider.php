<?php

namespace Playbloom\Satisfy\Provider;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use Silex\ServiceProviderInterface;
use Silex\Application;
use PhpCollection\Map;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\Context;
use Playbloom\Satisfy\Model\Manager;
use Playbloom\Satisfy\Model\JsonPersister;
use Playbloom\Satisfy\Model\FilePersister;
use Playbloom\Satisfy\Model\LockProcessor;

/**
 * Satis Silex service provider class
 *
 * @author Ludovic Fleury <ludo.fleury@gmail.com>
 */
class SatisServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['filesystem'] = $app->share(function () use ($app) {
            $client = S3Client::factory([
                'credentials' => [
                    'key'    => getenv('AWS_KEY'),
                    'secret' => getenv('AWS_SECRET'),
                ],
                'region' => getenv('AWS_REGION'),
                'version' => 'latest',
            ]);

            $adapter = new AwsS3Adapter($client, getenv('AWS_S3_BUCKET'));
            return new Filesystem($adapter);
        });

        $app['satis'] = $app->share(function () use ($app) {

            $serializer = SerializerBuilder::create()
                ->configureHandlers(function (HandlerRegistry $registry) use ($app) {
                    $registry->registerHandler('serialization', 'RepositoryCollection', 'json',
                        function ($visitor, Map $map, array $type, Context $context) use ($app) {
                            // We change the base type, and pass through possible parameters.
                            $type['name'] = 'array';
                            $data = $map->values();

                            if (!empty($app['satis.file_formatting'])) {
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
                ->build()
            ;

            $filePersister = new FilePersister($app['filesystem'], $app['satis.filename'], $app['satis.auditlog']);
            $jsonPersister = new JsonPersister($filePersister, $serializer, $app['satis.class']);

            return new Manager($jsonPersister) ;
        });

        $app['satis.lock'] = $app->share(function () use ($app) {
            return new LockProcessor($app['satis']);
        });
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }
}
