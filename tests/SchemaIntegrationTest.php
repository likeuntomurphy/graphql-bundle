<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests;

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\LikeuntomurphyGraphQLBundle;
use Likeuntomurphy\GraphQL\Tests\Fixtures\GlobalDocument\Widget;
use Likeuntomurphy\GraphQL\Tests\Fixtures\Manager\FullWidgetManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * @internal
 *
 * @coversNothing
 */
class SchemaIntegrationTest extends TestCase
{
    private Schema $schema;
    private FullWidgetManager $manager;

    protected function setUp(): void
    {
        $container = new ContainerBuilder();

        // Load bundle services.
        $bundle = new LikeuntomurphyGraphQLBundle();
        $bundle->build($container);
        $bundle->getContainerExtension()?->load([], $container);

        // Register fixture manager.
        $container->setDefinition(
            FullWidgetManager::class,
            (new Definition(FullWidgetManager::class))
                ->setPublic(true)
                ->addTag(GlobalObjectManagerInterface::TAG),
        );

        // Register denormalizer required by MutationFieldResolver.
        $container->setDefinition(
            DenormalizerInterface::class,
            new Definition(ObjectNormalizer::class),
        );

        // Make schema accessible for testing.
        $container->getDefinition(Schema::class)->setPublic(true);

        $container->compile();

        /** @var Schema $schema */
        $schema = $container->get(Schema::class);
        $this->schema = $schema;

        /** @var FullWidgetManager $manager */
        $manager = $container->get(FullWidgetManager::class);
        $this->manager = $manager;
    }

    public function testSchemaIsValid(): void
    {
        $this->schema->assertValid();

        $this->addToAssertionCount(1);
    }

    public function testIntrospectionReturnsWidgetType(): void
    {
        $result = $this->execute('{
            __type(name: "Widget") {
                name
                fields { name }
            }
        }');

        $this->assertNull($result['errors'] ?? null);
        $this->assertSame('Widget', $result['data']['__type']['name']);

        $fieldNames = array_column($result['data']['__type']['fields'], 'name');
        $this->assertContains('id', $fieldNames);
        $this->assertContains('name', $fieldNames);
    }

    public function testQueryNodeById(): void
    {
        $this->manager->seed(new Widget('42', 'Test Widget'));

        $result = $this->execute('
            query ($id: ID!) {
                node(id: $id) {
                    __typename
                    ... on Widget { id name }
                }
            }
        ', ['id' => base64_encode('Widget:42')]);

        $this->assertNull($result['errors'] ?? null);
        $this->assertSame('Widget', $result['data']['node']['__typename']);
        $this->assertSame(base64_encode('Widget:42'), $result['data']['node']['id']);
        $this->assertSame('Test Widget', $result['data']['node']['name']);
    }

    public function testQueryNodeReturnsNullForMissingId(): void
    {
        $result = $this->execute('
            query ($id: ID!) {
                node(id: $id) { __typename }
            }
        ', ['id' => base64_encode('Widget:999')]);

        $this->assertNull($result['data']['node']);
    }

    public function testQueryWidgetConnection(): void
    {
        $this->manager->seed(
            new Widget('1', 'Alpha'),
            new Widget('2', 'Beta'),
        );

        $result = $this->execute('{
            widgets {
                edges {
                    node { id name }
                    cursor
                }
                pageInfo { hasNextPage hasPreviousPage startCursor endCursor }
            }
        }');

        $this->assertNull($result['errors'] ?? null);

        $edges = $result['data']['widgets']['edges'];
        $this->assertCount(2, $edges);
        $this->assertSame('Alpha', $edges[0]['node']['name']);
        $this->assertSame('Beta', $edges[1]['node']['name']);

        $pageInfo = $result['data']['widgets']['pageInfo'];
        $this->assertFalse($pageInfo['hasNextPage']);
        $this->assertFalse($pageInfo['hasPreviousPage']);
    }

    public function testMutationResultUnionIncludesNodeNotFound(): void
    {
        $result = $this->execute('{
            __type(name: "WidgetMutationResult") {
                possibleTypes { name }
            }
        }');

        $this->assertNull($result['errors'] ?? null);

        $typeNames = array_column($result['data']['__type']['possibleTypes'], 'name');
        $this->assertContains('Widget', $typeNames);
        $this->assertContains('ValidationErrorList', $typeNames);
        $this->assertContains('NodeNotFound', $typeNames);
    }

    /**
     * @param array<string, mixed> $variables
     *
     * @return array<string, mixed>
     */
    private function execute(string $query, array $variables = []): array
    {
        return GraphQL::executeQuery($this->schema, $query, contextValue: [], variableValues: $variables)->toArray();
    }
}
