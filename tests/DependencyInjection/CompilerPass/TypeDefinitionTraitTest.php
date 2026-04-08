<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\DependencyInjection\CompilerPass;

use GraphQL\Type\Definition\ListOfType;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\TypeDefinitionTrait;
use Likeuntomurphy\GraphQL\TypeRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\TypeInfo\Type;

/**
 * @internal
 *
 * @covers \Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\TypeDefinitionTrait
 */
class TypeDefinitionTraitTest extends TestCase
{
    public function testListOfCreatesListOfDefinition(): void
    {
        $trait = new class {
            use TypeDefinitionTrait;

            public function doListOf(Reference $ref, ContainerBuilder $container): Reference
            {
                return $this->listOf($ref, $container);
            }
        };

        $container = new ContainerBuilder();
        $ref = new Reference('graphql.type.String');

        $result = $trait->doListOf($ref, $container);

        $expectedId = 'graphql.type.String.list_of';
        $this->assertSame($expectedId, (string) $result);
        $this->assertTrue($container->has($expectedId));

        $def = $container->getDefinition($expectedId);
        $this->assertSame(ListOfType::class, $def->getClass());
        $this->assertFalse($def->isPublic());
    }

    public function testListOfReusesExistingDefinition(): void
    {
        $trait = new class {
            use TypeDefinitionTrait;

            public function doListOf(Reference $ref, ContainerBuilder $container): Reference
            {
                return $this->listOf($ref, $container);
            }
        };

        $container = new ContainerBuilder();
        $ref = new Reference('graphql.type.String');

        $existing = new Definition(ListOfType::class)->setPublic(true);
        $container->setDefinition('graphql.type.String.list_of', $existing);

        $result = $trait->doListOf($ref, $container);

        $this->assertSame('graphql.type.String.list_of', (string) $result);
        $this->assertSame($existing, $container->getDefinition('graphql.type.String.list_of'));
    }

    public function testResolveTypeReferenceResolvesObjectType(): void
    {
        $trait = new class {
            use TypeDefinitionTrait;

            public function doResolve(Type $type, string $name): Reference
            {
                return $this->resolveTypeReference($type, $name);
            }
        };

        $type = Type::object(\stdClass::class);

        $result = $trait->doResolve($type, 'widget');

        $this->assertSame(TypeRegistry::TAG.'.stdClass', (string) $result);
    }
}
