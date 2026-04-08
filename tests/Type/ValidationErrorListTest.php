<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Type;

use GraphQL\Type\Definition\ListOfType;
use Likeuntomurphy\GraphQL\Type\ValidationError;
use Likeuntomurphy\GraphQL\Type\ValidationErrorList;
use Likeuntomurphy\GraphQL\TypeRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Type\ValidationErrorList
 */
class ValidationErrorListTest extends TestCase
{
    private ValidationErrorList $type;
    private ValidationError $errorType;

    protected function setUp(): void
    {
        $this->errorType = new ValidationError();

        $registry = new TypeRegistry(new ServiceLocator([
            'ValidationError' => fn () => $this->errorType,
        ]));

        $this->type = new ValidationErrorList($registry);
    }

    public function testNameIsValidationErrorList(): void
    {
        $this->assertSame('ValidationErrorList', $this->type->name);
    }

    public function testDescriptionIsSet(): void
    {
        $this->assertSame('Holds a list of validation errors for mutation input', $this->type->description);
    }

    public function testErrorsFieldIsListOfValidationError(): void
    {
        $field = $this->type->getField('errors');

        $this->assertInstanceOf(ListOfType::class, $field->getType());
        $this->assertSame($this->errorType, $field->getType()->getWrappedType());
    }
}
