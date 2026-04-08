<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Type;

use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ObjectType;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

class Mutation extends ObjectType
{
    public const string FIELD_TAG = 'graphql.mutation.field';

    /** @param iterable<FieldDefinition> $mutationFields */
    public function __construct(
        #[AutowireLocator(self::FIELD_TAG)] protected iterable $mutationFields,
    ) {
        parent::__construct([
            'description' => 'The root mutation type.',
            'fields' => iterator_to_array($this->mutationFields),
        ]);
    }
}
