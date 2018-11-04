<?php

namespace Tests\Playbloom\Satisfy\Traits;

use JsonSchema\Uri\UriRetriever;
use JsonSchema\Validator;

trait SchemaValidatorTrait
{
    /**
     * @return object
     */
    protected function getSatisSchema()
    {
        $retriever = new UriRetriever();

        return $retriever->retrieve('file://'.__DIR__.'/../../../../vendor/composer/satis/res/satis-schema.json');
    }

    protected function validateSchema($content, \stdClass $schema)
    {
        $validator = new Validator();
        $validator->check($content, $schema);
        $this->assertTrue($validator->isValid(), print_r($validator->getErrors(), true));
    }
}
