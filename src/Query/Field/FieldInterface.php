<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Query\Field;

use Likeuntomurphy\GraphQL\Type\Query;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(Query::FIELD_TAG)]
interface FieldInterface
{
}
