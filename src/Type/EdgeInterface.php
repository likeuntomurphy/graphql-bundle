<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Type;

use GraphQL\Type\Definition\InterfaceType;
use Likeuntomurphy\GraphQL\Field\Cursor;
use Likeuntomurphy\GraphQL\TypeRegistry;

class EdgeInterface extends InterfaceType
{
    public const string TYPE_NAME = 'EdgeInterface';
    public const string NODE_FIELD_DESCRIPTION = 'The current object wrapped by the edge.';

    public function __construct(
        TypeRegistry $typeRegistry,
        Cursor $cursor,
    ) {
        parent::__construct([
            'description' => 'Edge type',
            'fields' => [
                'node' => [
                    'type' => $typeRegistry->get(NodeInterface::TYPE_NAME),
                    'description' => self::NODE_FIELD_DESCRIPTION,
                ],
                'cursor' => $cursor,
            ],
            'resolveType' => fn () => null,
        ]);
    }
}
