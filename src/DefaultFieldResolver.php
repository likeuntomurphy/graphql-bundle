<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL;

use GraphQL\Type\Definition\ResolveInfo;
use Likeuntomurphy\GraphQL\Resolver\Field\DefaultFieldResolverInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class DefaultFieldResolver implements DefaultFieldResolverInterface
{
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct()
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * @param array<string, mixed> $args
     * @param array<string, mixed> $context
     */
    public function __invoke(
        mixed $source,
        array $args,
        array $context,
        ResolveInfo $info,
    ): mixed {
        if (is_object($source) && property_exists($source, $info->fieldName) && $this->propertyAccessor->isReadable($source, $info->fieldName)) {
            return $this->propertyAccessor->getValue($source, $info->fieldName);
        }

        if (is_array($source) && array_key_exists($info->fieldName, $source)) {
            return $source[$info->fieldName];
        }

        return null;
    }
}
