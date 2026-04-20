# Likeuntomurphy GraphQL Bundle

A code-first GraphQL server for Symfony. Define manager classes that implement capability interfaces, and the bundle generates a complete, Relay-compliant GraphQL schema at container compile time.

## Requirements

- PHP 8.5+
- Symfony 8.0+
- webonyx/graphql-php 15.30+
- Doctrine MongoDB ODM 2.16+ (optional, for `CursorPaginatedRepository`)

## Installation

```bash
composer require likeuntomurphy/graphql-bundle
```

Register the bundle:

```php
// config/bundles.php
return [
    Likeuntomurphy\GraphQL\LikeuntomurphyGraphQLBundle::class => ['all' => true],
];
```

## How it works

The entire GraphQL schema is built at **container compile time** through a series of compiler passes — no schema is constructed or rebuilt at runtime. The bundle scans for services tagged with `graphql.global_object_manager`, uses reflection to inspect your PHP classes, and registers every object type, input type, connection type, enum, query field, and mutation field as tagged service definitions in the container. Manager interfaces declare what operations each type supports (read, create, update, delete, list), and the compiler passes generate the corresponding schema elements automatically.

At runtime, the `TypeRegistry` resolves types lazily via a `ServiceLocator`. Types are instantiated only when first accessed during query execution, not upfront. This means the compiled container holds the full schema definition, but individual type objects are created on demand — keeping memory usage proportional to the query being executed rather than the total schema size.

The bundle is **persistence-agnostic** — and more broadly, data source–agnostic. The only contract for a global object is implementing `GlobalObjectInterface`, which requires a single `getId()` method. A manager is free to read from a database, a document store, a REST API, a federated GraphQL service, an in-memory fixture, or anything else; the bundle doesn't know or care whether the manager persists anything at all.

## Design principles

Opinionated bundles are only useful if the opinions are legible. Beyond the compile-time schema construction and persistence-agnostic defaults described above, this bundle holds two load-bearing opinions:

**1. Relay Connections are the canonical answer to lists-of-things-with-identity.** Pagination is not configurable — it is the default and the only path. If you don't need pagination today, you will tomorrow; building on connections from the start avoids the eventual rewrite when your list grows. When a consumer genuinely wants a flat list, `connection.edges[].node` gives it to them without the schema abandoning its stance.

**2. Connection vs list is a question of identity, not size.** Relations to types implementing `GlobalObjectInterface` are always connections. Relations to local or embedded objects (types without independent identity) are always lists. Size is not a factor: a `User.orders` connection starts small but is unbounded; an `Order.lineItems` list is bounded by the parent's inherent data regardless of count. Mixing the axes leads to "why is this a connection when it has three items?" confusion; keeping the axis on identity keeps the schema coherent.

## Defining a global object

A global object is any entity exposed through the GraphQL schema with a globally unique ID. It needs two things: a document class and a manager.

### Document

```php
use Likeuntomurphy\GraphQL\GlobalObjectInterface;
use Likeuntomurphy\GraphQL\Attribute as GraphQL;

class Widget implements GlobalObjectInterface
{
    public protected(set) string $id;

    #[GraphQL\Description('The display name of the widget')]
    public string $name;

    public ?string $color = null;

    public function getId(): string
    {
        return $this->id;
    }
}
```

Public properties become GraphQL fields. Nullable properties become nullable fields. The `id` field is automatically replaced with a Relay-style global ID (`base64("TypeName:rawId")`).

### Manager

```php
use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Likeuntomurphy\GraphQL\ListableManagerInterface;
use Likeuntomurphy\GraphQL\CreatableManagerInterface;
use Likeuntomurphy\GraphQL\UpdatableManagerInterface;
use Likeuntomurphy\GraphQL\DeletableManagerInterface;

class WidgetManager implements
    GlobalObjectManagerInterface,
    CreatableManagerInterface,
    UpdatableManagerInterface,
    DeletableManagerInterface,
    ListableManagerInterface
{
    public static function getManagedGlobalObject(): string
    {
        return Widget::class;
    }

    public static function getManagedDataTransferObject(): string
    {
        return WidgetDto::class;
    }

    public function read(string $id): ?object { /* ... */ }
    public function create(object $dto, object $document, array $validationGroups = []): object { /* ... */ }
    public function update(object $dto, object $document, array $validationGroups = []): object { /* ... */ }
    public function delete(object $document): object { /* ... */ }
    public function list(CursorPaginationParams $params, ?callable $filter = null): PaginatedResults { /* ... */ }
}
```

Each interface you implement generates schema elements:

| Interface | Generates |
|---|---|
| `GlobalObjectManagerInterface` | Object type with `NodeInterface`, `node(id: ID!)` resolution via `read()` |
| `ListableManagerInterface` | Root query field with cursor pagination (e.g. `widgets`) |
| `CreatableManagerInterface` | Mutation field (e.g. `createWidget`) |
| `UpdatableManagerInterface` | Mutation field (e.g. `updateWidget`) |
| `DeletableManagerInterface` | Mutation field (e.g. `deleteWidget`) |

## Attributes

### `#[Description(string $description)]`

Sets the GraphQL description for a property.

```php
#[GraphQL\Description('The widget color in hex format')]
public string $color;
```

### `#[Exclude]`

Excludes a property from the GraphQL schema.

```php
#[GraphQL\Exclude]
public string $internalField;
```

### `#[Name(string $name)]`

Overrides the GraphQL type name (defaults to the class short name).

```php
#[GraphQL\Name('SpecialWidget')]
class Widget { /* ... */ }
```

### `#[IdField]`

Marks a DTO property as a global ID field. The bundle automatically decodes it from the Relay global ID format before passing it to the manager.

```php
class WidgetDto
{
    #[GraphQL\IdField]
    public string $parentId;

    public string $name;
}
```

### `#[Resolver(string $resolver)]`

Assigns a custom field resolver to a property.

```php
#[GraphQL\Resolver(WidgetColorResolver::class)]
public ?string $computedColor = null;
```

The resolver class must be invocable:

```php
class WidgetColorResolver
{
    public function __invoke(Widget $source, mixed $args, mixed $context, ResolveInfo $info): ?string
    {
        return $source->color ?? '#000000';
    }
}
```

### `#[AsConnection(string $fieldName)]`

Marks a manager method as a nested connection field on the parent type.

```php
class WidgetManager implements GlobalObjectManagerInterface
{
    /** @return PaginatedResults<Part> */
    #[AsConnection('parts')]
    public function findParts(Widget $widget, CursorPaginationParams $params): PaginatedResults
    {
        // ...
    }
}
```

This generates a `parts` field on the `Widget` type with standard cursor pagination arguments (`first`, `after`). The method's generic return type determines the child type.

## Validation groups

Implement `ValidatableManagerInterface` to add a `validationGroups` argument to create/update mutations:

```php
use Likeuntomurphy\GraphQL\ValidatableManagerInterface;

class WidgetManager implements GlobalObjectManagerInterface, CreatableManagerInterface, ValidatableManagerInterface
{
    public static function getValidationGroupEnum(): string
    {
        return WidgetValidationGroup::class;
    }
}
```

The enum is exposed as a GraphQL enum type, and the mutation accepts a list of its cases (`[WidgetValidationGroup!]`). The selected cases are passed directly as the `$validationGroups` parameter to `create()` and `update()`.

## Mutation results

All mutations return a union type: `{TypeName}MutationResult = TypeName | ValidationErrorList | NodeNotFound`. The bundle generates these automatically.

- On success, the resolved object is returned.
- On validation failure, a `ValidationErrorList` with field paths and messages is returned.
- On update/delete of a missing ID, `NodeNotFound` is returned.

## Cursor pagination

The bundle implements Relay-style cursor pagination. Root list queries and nested connection fields accept `first` (number of items) and `after` (cursor) arguments, and return connection types with `edges`, `pageInfo`, `node`, and `cursor` fields.

### Configuration

```yaml
# config/packages/foster_made_graphql.yaml
foster_made_graphql:
    pagination:
        limit: 100  # Maximum items per page (default: 100)
```

### Repository

The bundle provides `CursorPaginatedRepository` as a base repository class for Doctrine MongoDB ODM:

```yaml
# config/packages/doctrine_mongodb.yaml
doctrine_mongodb:
    document_managers:
        default:
            default_document_repository_class: 'Likeuntomurphy\GraphQL\Repository\Doctrine\ODM\MongoDB\CursorPaginatedRepository'
```

This adds a `findWithPageInfo(CursorPaginationParams $params, ?callable $filter = null)` method to all repositories, returning `PaginatedResults` with proper page info.

## Custom query fields

Implement `FieldInterface` and tag with `graphql.query.field`:

```php
use Likeuntomurphy\GraphQL\Query\Field\FieldInterface;
use GraphQL\Type\Definition\FieldDefinition;

class ViewerField extends FieldDefinition implements FieldInterface
{
    public function __construct(TypeRegistry $typeRegistry)
    {
        parent::__construct([
            'name' => 'viewer',
            'type' => $typeRegistry->get('Viewer'),
            'resolve' => fn ($source, $args, $context) => $context['viewer'] ?? null,
        ]);
    }
}
```

Fields in `src/Query/Field/` are auto-tagged by the bundle's service configuration.

## Custom types

Implement `TypeInterface` and place in `src/Type/` (auto-tagged with `graphql.type`):

```php
use Likeuntomurphy\GraphQL\Type\TypeInterface;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class Viewer extends ObjectType implements TypeInterface
{
    public function __construct()
    {
        parent::__construct([
            'fields' => [
                'email' => ['type' => Type::string()],
            ],
        ]);
    }
}
```

## Persisted queries

The bundle provides interfaces for persisted query support:

- `PersistedQueryStoreInterface` — reads a stored query by ID
- `PersistedQueryRegistrarInterface` — saves a validated query by ID (for automatic persisted queries)

The `GraphQLRequest` model accepts either a `query` string or an `id` referencing a persisted query. When `id` is provided, `query` is not required.

## Type registry

The `TypeRegistry` service provides access to all registered GraphQL types by name:

```php
$typeRegistry->get('Widget');        // ObjectType
$typeRegistry->get('DateTime');      // ScalarType
$typeRegistry->has('Widget');        // bool
```

Types are registered as tagged services (`graphql.type`) and loaded lazily via Symfony's `ServiceLocator`.

## Compiler passes

The bundle registers 8 compiler passes in order:

1. **StandardTypePass** — Registers built-in scalar types (String, Int, Float, Boolean, ID)
2. **TypeNamePass** — Resolves type names from class short names or `#[Name]` attributes
3. **GlobalObjectTypePass** — Creates ObjectType definitions from manager-registered global objects
4. **LocalObjectTypePass** — Resolves nested/embedded object types referenced by global objects
5. **EnumTypePass** — Registers PHP enums as GraphQL enum types
6. **QueryFieldPass** — Generates root query fields and connection types for listable managers
7. **ConnectionFieldPass** — Processes `#[AsConnection]` attributes for nested connection fields
8. **MutationFieldPass** — Generates mutation fields, input types, and result union types

## Testing

```bash
vendor/bin/phpunit
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run
```

### Integration test pattern

```php
use Likeuntomurphy\GraphQL\LikeuntomurphyGraphQLBundle;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use Symfony\Component\DependencyInjection\ContainerBuilder;

$container = new ContainerBuilder();

$bundle = new LikeuntomurphyGraphQLBundle();
$bundle->build($container);
$bundle->getContainerExtension()?->load([], $container);

// Register your manager
$container->setDefinition(WidgetManager::class,
    (new Definition(WidgetManager::class))
        ->setPublic(true)
        ->addTag(GlobalObjectManagerInterface::TAG),
);

$container->compile();

$schema = $container->get(Schema::class);
$result = GraphQL::executeQuery($schema, '{ widgets { edges { node { id name } } } }');
```
