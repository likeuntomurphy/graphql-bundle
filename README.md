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

The bundle's extension prepends `framework.serializer: { enabled: true }` because `MutationFieldResolver` autowires `Symfony\Component\Serializer\Normalizer\DenormalizerInterface` and relies on `AbstractNormalizer::OBJECT_TO_POPULATE`, which `ObjectNormalizer` (and equivalent denormalizers) provide. App-level config overrides still win; the prepend only supplies a default when nothing is configured.

## How it works

The schema is a container artifact. Every object type, input type, connection, enum, query field, and mutation field is registered as a tagged service definition by a compiler pass — there is no schema assembly, reflection scan, or attribute parsing at request time.

- **Compile once, serve many.** The schema is built during container compilation and cached with the container. Production deploys warm it; requests consume it.
- **Zero per-request discovery.** Managers, types, and field handlers are already wired as services or service-locator entries by the time a request arrives. The resolver just asks the container.
- **Lazy type instantiation.** `TypeRegistry` resolves types through a `ServiceLocator`, so individual type objects are only materialized when a query actually touches them. Memory scales with the query, not the schema.
- **Debuggable via standard tooling.** `debug:container graphql.type.*`, `graphql.mutation.field.*`, and `graphql.global_object_manager` show you the live schema. Nothing is hidden in a runtime registry.
- **Symfony-native extension.** Attributes autoconfigure managers, compiler passes generate schema elements, standard service decoration overrides them. No parallel DI, no custom kernel.

The bundle is also **data-source agnostic.** The only contract for a global object is `GlobalObjectInterface::getId()`. A manager can read from a database, a document store, a REST API, a federated GraphQL service, an in-memory fixture, or anything else — the bundle doesn't know or care whether it persists anything at all.

## Design principles

Opinionated bundles are only useful if the opinions are legible. Beyond the compile-time schema construction and persistence-agnostic defaults described above, this bundle holds two load-bearing opinions:

**1. Relay Connections are the canonical answer to lists-of-things-with-identity.** Pagination is not configurable — it is the default and the only path. If you don't need pagination today, you will tomorrow; building on connections from the start avoids the eventual rewrite when your list grows. When a consumer genuinely wants a flat list, `connection.edges[].node` gives it to them without the schema abandoning its stance.

**2. Connection vs list is a question of identity, not size.** Relations to types implementing `GlobalObjectInterface` are always connections. Relations to local or embedded objects (types without independent identity) are always lists. Size is not a factor: a `User.orders` connection starts small but is unbounded; an `Order.lineItems` list is bounded by the parent's inherent data regardless of count. Mixing the axes leads to "why is this a connection when it has three items?" confusion; keeping the axis on identity keeps the schema coherent.

## Defining a global object

A global object is any entity exposed through the GraphQL schema with a globally unique ID. It needs two things: a document class and a manager.

### Document

```php
use Likeuntomurphy\GraphQL\Attribute as GraphQL;
use Likeuntomurphy\GraphQL\Attribute\GlobalObject;
use Likeuntomurphy\GraphQL\GlobalObjectInterface;

#[GlobalObject(manager: WidgetManager::class)]
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

The `#[GlobalObject]` attribute is the discovery entry point — the bundle finds entities via Symfony 7.3 resource-tag autoconfiguration and links each to its manager. Entities are never instantiated by the container.

Public properties become GraphQL fields. Nullable properties become nullable fields. The `id` field is automatically replaced with a Relay-style global ID (`base64("TypeName:rawId")`). The same properties are reflected as input fields for create and update mutations — readonly, excluded, and `id` properties are filtered out of mutation args.

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
    public function read(string $id): ?object { /* ... */ }
    public function create(object $document): object { /* ... */ }
    public function update(object $document): object { /* ... */ }
    public function delete(object $document): object { /* ... */ }
    public function list(CursorPaginationParams $params, ?callable $filter = null): PaginatedResults { /* ... */ }
}
```

Each interface you implement generates schema elements:

| Interface | Generates |
| --- | --- |
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

### Relations

Typed references between global objects are inferred automatically. If a property on one global object is typed to another class that also carries `#[GlobalObject]`, the bundle generates an `{propertyName}Id: ID` mutation arg, decodes the incoming node ID, and resolves the target via the referenced class's manager before handing the populated entity to the current manager.

```php
#[GlobalObject(manager: AttachmentManager::class)]
class Attachment implements GlobalObjectInterface
{
    protected string $id;

    public Project $project;

    public string $url;
}
```

On a `createAttachment` mutation, clients send `projectId: ID!`; the bundle resolves it to a `Project` instance via `ProjectManager::read()` and sets `$attachment->project`. If the ID doesn't resolve, the mutation returns a `ValidationErrorList` with a constraint violation on the property.

### `#[Type(string $name)]`

Overrides the GraphQL type for a property, replacing the default primitive mapping or object-reference resolution. The name must match a type registered with `graphql.type` — either one of the bundle's built-in scalars (`Email`, `Url`, `Uuid`, `NonEmptyString`, `DateTime`) or a custom type the app has registered.

```php
use Likeuntomurphy\GraphQL\Attribute\Type;

class User implements GlobalObjectInterface
{
    #[Type('Email')]
    public string $email;

    #[Type('Url')]
    public ?string $website;

    #[Type('NonEmptyString')]
    public string $displayName;
}
```

The PHP type stays what it was (`string`, `?string`); only the schema's advertised scalar changes. This is the seam for stricter input validation at the GraphQL boundary — malformed values are rejected by the scalar before any resolver runs.

### Built-in scalars

The bundle ships a small opinionated set of scalars beyond GraphQL's standard `Int`, `Float`, `String`, `Boolean`, and `ID`:

- `DateTime` — ISO-8601 date/time, used automatically for `\DateTimeImmutable` properties.
- `Email` — RFC 5322 email address.
- `Url` — RFC 3986 URL.
- `Uuid` — RFC 9562 UUID.
- `NonEmptyString` — non-empty, non-whitespace string.

Reference any of them with `#[Type('Email')]` (etc.) on a `string` property. Apps register additional scalars by placing them in their own type namespace tagged with `graphql.type` and reference them through the same attribute.

The shipped scalars validate with native PHP (`filter_var`, regex, `trim`) to stay dependency-free, and serve as models for your own. Because registered types are ordinary Symfony services, a custom scalar can inject anything it needs — `ValidatorInterface` to match a specific `Assert\Email` mode, a domain service, a repository, whatever:

```php
use GraphQL\Error\Error;
use GraphQL\Type\Definition\ScalarType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StrictEmail extends ScalarType
{
    public string $name = 'StrictEmail';

    public function __construct(private ValidatorInterface $validator) {}

    public function parseValue(mixed $value): string
    {
        if (!is_string($value)) {
            throw new Error('StrictEmail must be a string.');
        }

        $violations = $this->validator->validate($value, new Assert\Email(mode: Assert\Email::VALIDATION_MODE_STRICT));

        if (count($violations) > 0) {
            throw new Error($violations[0]->getMessage());
        }

        return $value;
    }

    // serialize() and parseLiteral() follow the same shape as Email::class.
}
```

Autowiring handles the injection. Use `#[Type('StrictEmail')]` on properties where you need it.

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

### `#[GlobalEnum]`

Registers a PHP enum as a GraphQL enum type without requiring it to be referenced by any field. Useful for enums that surface client-side (form options, dropdown values) but aren't the type of a document property or mutation arg.

```php
use Likeuntomurphy\GraphQL\Attribute\GlobalEnum;

#[GlobalEnum]
enum PaymentMethod: string
{
    case CreditCard = 'credit_card';
    case WireTransfer = 'wire_transfer';
}
```

Clients access values via standard GraphQL introspection: `{ __type(name: "PaymentMethod") { enumValues { name } } }`.

Enums already referenced as property or argument types are picked up automatically without this attribute.

### Nested connections (convention)

Any property on a global object whose element type is another global object becomes a nested connection field automatically — no attribute required.

```php
use Doctrine\Common\Collections\Collection;

class Widget implements GlobalObjectInterface
{
    /** @var Collection<int, Part> */
    public Collection $parts;

    // ...
}

class WidgetManager implements GlobalObjectManagerInterface
{
    /** @return PaginatedResults<Part> */
    public function paginateParts(Widget $widget, CursorPaginationParams $params): PaginatedResults
    {
        // ...
    }
}
```

This generates a `parts` field on the `Widget` type with standard cursor pagination arguments (`first`, `after`). The manager method must be named `paginate<Property>` with the signature `(Parent, CursorPaginationParams): PaginatedResults<Child>`. A missing method is a compile-time error.

The property type only needs to be a collection shape — `Collection<T>`, `list<T>`, `array<int, T>`, `iterable<T>`, etc. are all accepted; the bundle reads the element type from the docblock and doesn't care which collection class (if any) backs it.

## Validation

Validation is the manager's responsibility. Managers that want to reject invalid state throw `Symfony\Component\Validator\Exception\ValidationFailedException`; the resolver catches it and returns a `ValidationErrorList` in the mutation response. The bundle takes no opinion on which groups to apply or when — that's the manager's call, operation by operation.

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
7. **ConnectionFieldPass** — Generates nested connection fields from collection-of-global-object properties on entities
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
