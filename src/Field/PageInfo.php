<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Field;

use GraphQL\Type\Definition\FieldDefinition;
use Likeuntomurphy\GraphQL\Type\PageInfo as PageInfoType;

class PageInfo extends FieldDefinition
{
    public function __construct(
        PageInfoType $pageInfo,
    ) {
        parent::__construct([
            'name' => 'pageInfo',
            'type' => $pageInfo,
            'description' => 'Information to aid in pagination.',
        ]);
    }
}
