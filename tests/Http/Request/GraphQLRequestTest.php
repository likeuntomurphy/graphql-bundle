<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Http\Request;

use Likeuntomurphy\GraphQL\Http\Request\GraphQLRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\When;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\Http\Request\GraphQLRequest
 */
class GraphQLRequestTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
        ;
    }

    public function testDefaults(): void
    {
        $request = new GraphQLRequest();

        $this->assertNull($request->id);
        $this->assertNull($request->query);
        $this->assertNull($request->operationName);
        $this->assertSame([], $request->variables);
    }

    public function testQueryHasConditionalConstraint(): void
    {
        $rp = new \ReflectionProperty(GraphQLRequest::class, 'query');

        $this->assertCount(1, $rp->getAttributes(When::class));
    }

    public function testQueryRequiredWhenIdAbsent(): void
    {
        $request = new GraphQLRequest();

        $violations = $this->validator->validate($request);

        $this->assertGreaterThan(0, \count($violations));
    }

    public function testQueryNotRequiredWhenIdPresent(): void
    {
        $request = new GraphQLRequest();
        $request->id = 'abc123';

        $violations = $this->validator->validate($request);

        $this->assertCount(0, $violations);
    }

    public function testValidWithQueryAndNoId(): void
    {
        $request = new GraphQLRequest();
        $request->query = '{ viewer { email } }';

        $violations = $this->validator->validate($request);

        $this->assertCount(0, $violations);
    }
}
