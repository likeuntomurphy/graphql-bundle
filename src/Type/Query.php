<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Type;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ObjectType;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

class Query extends ObjectType
{
    public const string FIELD_TAG = 'graphql.query.field';

    /** @param iterable<FieldDefinition> $queryFields */
    public function __construct(
        #[AutowireLocator(self::FIELD_TAG)] protected iterable $queryFields,
    ) {
        parent::__construct([
            'description' => 'The root query type.',
            'fields' => iterator_to_array($this->queryFields),
        ]);
    }
}
