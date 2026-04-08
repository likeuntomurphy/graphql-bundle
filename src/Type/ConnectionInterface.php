<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Type;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;
use Likeuntomurphy\GraphQL\Field\PageInfo as PageInfoField;
use Likeuntomurphy\GraphQL\TypeRegistry;

class ConnectionInterface extends InterfaceType
{
    public const string TYPE_NAME = 'ConnectionInterface';
    public const string EDGES_FIELD_DESCRIPTION = 'A list of edges provided by this connection.';

    public function __construct(
        TypeRegistry $typeRegistry,
        PageInfoField $pageInfoField,
    ) {
        parent::__construct([
            'description' => 'Connection type',
            'fields' => [
                'edges' => [
                    'type' => Type::listOf($typeRegistry->get(EdgeInterface::TYPE_NAME)), // @phpstan-ignore argument.type
                    'description' => self::EDGES_FIELD_DESCRIPTION,
                ],
                'pageInfo' => $pageInfoField,
            ],
            'resolveType' => fn () => null,
        ]);
    }
}
