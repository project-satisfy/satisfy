<?php

/*
 * (c) DreamCheaper Global GmbH <info@dreamcheaper.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace tests\Playbloom\Satisfy\Provider;

use Playbloom\Satisfy\Model\Manager;
use Silex\Application;
use Silex\WebTestCase;

class SatisServiceProviderTest extends WebTestCase
{
    /** @var object */
    protected $schema;

    /** @var \JsonSchema\Validator */
    protected $validator;

    /**
     * @return Application
     */
    public function createApplication()
    {
        $app = include __DIR__ . '/../../../../app/bootstrap.php';
        return $app;
    }

    public function setUp()
    {
        parent::setUp();

        $retriever = new \JsonSchema\Uri\UriRetriever;
        $this->schema = $retriever->retrieve(
            'file://'.__DIR__.'/../../../../vendor/composer/satis/res/satis-schema.json'
        );

        $this->validator = new \JsonSchema\Validator();
    }

    /**
     * @dataProvider configFileProvider
     */
    public function testConfigIsMatchingSatisSchema($configFilename)
    {
        $content = file_get_contents($configFilename);
        $temporaryConfig = tempnam(sys_get_temp_dir(), 'config');
        file_put_contents($temporaryConfig, $content);

        $this->app['satis.filename'] = $temporaryConfig;

        /** @var Manager $manager */
        $manager = $this->app['satis'];
        $manager->addAll(array());

        $this->validator->check(json_decode(file_get_contents($temporaryConfig)), $this->schema);
        $this->assertTrue($this->validator->isValid(), print_r($this->validator->getErrors(), true));

        $this->assertJsonFileEqualsJsonFile($configFilename, $temporaryConfig);

        unlink($temporaryConfig);
    }

    /**
     * @return array
     */
    public function configFileProvider()
    {
        return [
            [__DIR__ . '/../../../fixtures/satis-minimal.json'],
            [__DIR__ . '/../../../fixtures/satis-full.json'],
        ];
    }
}
