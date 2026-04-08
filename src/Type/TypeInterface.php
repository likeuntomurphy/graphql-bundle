<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Type;

use Likeuntomurphy\GraphQL\TypeRegistry;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(TypeRegistry::TAG)]
interface TypeInterface
{
}
