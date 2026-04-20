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
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

        // Register a validator that runs no constraints (there are none declared on Widget).
        $container->setDefinition(
            ValidatorInterface::class,
            (new Definition(ValidatorInterface::class))
                ->setFactory([Validation::class, 'createValidator']),
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

    public function testCreateMutationPersistsAndReturnsWidget(): void
    {
        $result = $this->execute('
            mutation ($name: String!) {
                createWidget(name: $name) {
                    ... on Widget { id name }
                }
            }
        ', ['name' => 'Gamma']);

        $this->assertNull($result['errors'] ?? null);
        $this->assertSame('Gamma', $result['data']['createWidget']['name']);

        $encodedId = $result['data']['createWidget']['id'];
        $this->assertSame('Widget:1', base64_decode($encodedId, true));
        $this->assertSame('Gamma', $this->manager->read('1')?->name);
    }

    public function testUpdateMutationAppliesDtoToStoredWidget(): void
    {
        $this->manager->seed(new Widget('7', 'Before'));

        $result = $this->execute('
            mutation ($id: ID!, $name: String!) {
                updateWidget(id: $id, name: $name) {
                    ... on Widget { id name }
                    ... on NodeNotFound { id }
                }
            }
        ', ['id' => base64_encode('Widget:7'), 'name' => 'After']);

        $this->assertNull($result['errors'] ?? null);
        $this->assertSame('After', $result['data']['updateWidget']['name']);
        $this->assertSame('After', $this->manager->read('7')?->name);
    }

    public function testUpdateMutationReturnsNodeNotFoundForMissingId(): void
    {
        $encodedId = base64_encode('Widget:999');

        $result = $this->execute('
            mutation ($id: ID!, $name: String!) {
                updateWidget(id: $id, name: $name) {
                    ... on Widget { name }
                    ... on NodeNotFound { id }
                }
            }
        ', ['id' => $encodedId, 'name' => 'ignored']);

        $this->assertNull($result['errors'] ?? null);
        $this->assertSame($encodedId, $result['data']['updateWidget']['id']);
    }

    public function testDeleteMutationRemovesStoredWidget(): void
    {
        $this->manager->seed(new Widget('9', 'Doomed'));

        $result = $this->execute('
            mutation ($id: ID!) {
                deleteWidget(id: $id) {
                    ... on Widget { name }
                    ... on NodeNotFound { id }
                }
            }
        ', ['id' => base64_encode('Widget:9')]);

        $this->assertNull($result['errors'] ?? null);
        $this->assertSame('Doomed', $result['data']['deleteWidget']['name']);
        $this->assertNull($this->manager->read('9'));
    }

    public function testDeleteMutationReturnsNodeNotFoundForMissingId(): void
    {
        $encodedId = base64_encode('Widget:999');

        $result = $this->execute('
            mutation ($id: ID!) {
                deleteWidget(id: $id) {
                    ... on Widget { name }
                    ... on NodeNotFound { id }
                }
            }
        ', ['id' => $encodedId]);

        $this->assertNull($result['errors'] ?? null);
        $this->assertSame($encodedId, $result['data']['deleteWidget']['id']);
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
