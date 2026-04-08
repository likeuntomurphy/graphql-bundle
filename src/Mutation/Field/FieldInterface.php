<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Mutation\Field;

use Likeuntomurphy\GraphQL\Type\Mutation;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(Mutation::FIELD_TAG)]
interface FieldInterface
{
}
