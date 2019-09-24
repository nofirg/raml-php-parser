<?php

namespace Raml\Tests\Types;

use PHPUnit\Framework\TestCase;
use Raml\ApiDefinition;
use Raml\Parser;
use Raml\Types\TypeValidationError;

class ObjectTypeTest extends TestCase
{
    /**
     * @var ApiDefinition
     */
    private $apiDefinition;

    protected function setUp()
    {
        parent::setUp();
        $this->apiDefinition = (new Parser())->parse(__DIR__ . '/../fixture/object_types.raml');
    }

    /**
     * @test
     * @dataProvider getValidObjectDataProvider
     */
    public function shouldCorrectlyValidateObjectType($validateObject)
    {
        $resource = $this->apiDefinition->getResourceByUri('/actors/1');
        $method = $resource->getMethod('get');
        $response = $method->getResponse(200);
        $body = $response->getBodyByType('application/json');
        $type = $body->getType();

        $type->validate($validateObject);

        $this->assertTrue($type->isValid());
    }

    public function getValidObjectDataProvider()
    {
        return [
            [
                [
                    'fistName' => 'Jackie',
                    'lastName' => 'Сhan',
                    'requiredAdditionalInfo1' => 'Cool fighter',
                ],
            ],
            [
                [
                    'fistName' => 'Jackie',
                    'lastName' => 'Сhan',
                    'requiredAdditionalInfo2' => 'Best fighter',
                    'notRequiredAdditionalInfo2' => 'Stuntman',
                ],
            ],
            [
                [
                    'fistName' => 'Jackie',
                    'lastName' => 'Сhan',
                    'requiredAdditionalInfo1' => 'Best fighter',
                    'requiredAdditionalInfo2' => 'And comic',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getNotValidObjectDataProvider
     */
    public function shouldNotCorrectlyValidateObjectType($notValidateObject, $expectedErrors)
    {
        $resource = $this->apiDefinition->getResourceByUri('/actors/1');
        $method = $resource->getMethod('get');
        $response = $method->getResponse(200);
        $body = $response->getBodyByType('application/json');
        $type = $body->getType();

        $type->validate($notValidateObject);

        $this->assertFalse($type->isValid());
        $this->assertEquals($expectedErrors, $type->getErrors());
    }

    public function getNotValidObjectDataProvider()
    {
        $requiredPropertyName = '/^requiredAdditionalInfo\d+$/';
        $notRequiredPropertyName = '/^notRequiredAdditionalInfo\d+$/';

        return [
            [
                $value = 'Jackie Сhan - cool fighter and stuntman',
                [TypeValidationError::unexpectedValueType('Actor', 'object', $value)],
            ],
            [
                [
                    'fistName' => 'Jackie',
                    'lastName' => 'Сhan',
                ],
                [TypeValidationError::missingRequiredProperty($requiredPropertyName)],
            ],
            [
                [
                    'fistName' => 'Jackie',
                    'lastName' => 'Сhan',
                    'requiredAdditionalInfo3' => $value = 100500,
                    'notRequiredAdditionalInfo3' => 'Stuntman',
                ],
                [TypeValidationError::unexpectedValueType($requiredPropertyName, 'string', $value)],
            ],
            [
                [
                    'fistName' => 'Jackie',
                    'lastName' => 'Сhan',
                    'notRequiredAdditionalInfo3' => $value = 100500,
                ],
                [
                    TypeValidationError::missingRequiredProperty($requiredPropertyName),
                    TypeValidationError::unexpectedValueType($notRequiredPropertyName, 'string', $value),
                ],
            ],
            [
                [
                    'fistName' => 'Jackie',
                    'lastName' => 'Сhan',
                    'requiredAdditionalInfo1' => 'Сhan',
                    'notRequiredAdditionalInfo1' => $value1 = 100500,
                    'notRequiredAdditionalInfo2' => $value2 = [],
                    'notRequiredAdditionalInfo3' => $value3 = 3.14,
                ],
                [
                    TypeValidationError::unexpectedValueType($notRequiredPropertyName, 'string', $value1),
                    TypeValidationError::unexpectedValueType($notRequiredPropertyName, 'string', $value2),
                    TypeValidationError::unexpectedValueType($notRequiredPropertyName, 'string', $value3),
                ],
            ],
            [
                [
                    'fistName' => 'Jackie',
                    'lastName' => 'Сhan',
                    'requiredAdditionalInfo1' => $value1 = 100500,
                    'requiredAdditionalInfo2' => $value2 = 3.14,
                ],
                [
                    TypeValidationError::unexpectedValueType($requiredPropertyName, 'string', $value1),
                    TypeValidationError::unexpectedValueType($requiredPropertyName, 'string', $value2),
                ],
            ],
        ];
    }
}
