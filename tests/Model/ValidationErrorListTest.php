<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Model;

use Likeuntomurphy\GraphQL\Model\ValidationError;
use Likeuntomurphy\GraphQL\Model\ValidationErrorList;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Model\ValidationErrorList
 */
class ValidationErrorListTest extends TestCase
{
    public function testConstructorSetsErrors(): void
    {
        $errors = [new ValidationError('name', 'must not be blank'), new ValidationError('email', 'is invalid')];
        $list = new ValidationErrorList($errors);

        $this->assertCount(2, $list->errors);
        $this->assertSame('name', $list->errors[0]->path);
        $this->assertSame('is invalid', $list->errors[1]->message);
    }

    public function testFromConstraintViolationListCreatesInstance(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('must not be blank', '', [], null, 'name', null),
            new ConstraintViolation('is invalid', '', [], null, 'email', null),
        ]);

        $list = ValidationErrorList::fromConstraintViolationList($violations);

        $this->assertInstanceOf(ValidationErrorList::class, $list);
        $this->assertCount(2, $list->errors);
        $this->assertSame('name', $list->errors[0]->path);
        $this->assertSame('must not be blank', $list->errors[0]->message);
        $this->assertSame('email', $list->errors[1]->path);
        $this->assertSame('is invalid', $list->errors[1]->message);
    }

    public function testFromConstraintViolationListHandlesEmptyList(): void
    {
        $list = ValidationErrorList::fromConstraintViolationList(new ConstraintViolationList());

        $this->assertSame([], $list->errors);
    }

    public function testErrorsAreValidationErrorInstances(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('bad', '', [], null, 'field', null),
        ]);

        $list = ValidationErrorList::fromConstraintViolationList($violations);

        $this->assertInstanceOf(ValidationError::class, $list->errors[0]);
    }
}
