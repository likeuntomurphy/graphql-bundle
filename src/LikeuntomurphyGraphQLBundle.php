<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL;

use Likeuntomurphy\GraphQL\Attribute\GlobalEnum;
use Likeuntomurphy\GraphQL\Attribute\GlobalObject;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\ConnectionFieldPass;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\EnumTypePass;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\GlobalObjectTypePass;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\LocalObjectTypePass;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\MutationFieldPass;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\QueryFieldPass;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\StandardTypePass;
use Likeuntomurphy\GraphQL\DependencyInjection\CompilerPass\TypeNamePass;
use Likeuntomurphy\GraphQL\DependencyInjection\GraphQLExtension;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class LikeuntomurphyGraphQLBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->registerAttributeForAutoconfiguration(
            GlobalObject::class,
            static function (ChildDefinition $def, GlobalObject $attr): void {
                $def->addResourceTag(GlobalObject::RESOURCE_TAG, ['manager' => $attr->manager]);
            },
        );

        $container->registerAttributeForAutoconfiguration(
            GlobalEnum::class,
            static function (ChildDefinition $def): void {
                $def->addResourceTag(GlobalEnum::RESOURCE_TAG);
            },
        );

        $passes = [
            new StandardTypePass(),
            new TypeNamePass(),
            new GlobalObjectTypePass(),
            new LocalObjectTypePass(),
            new EnumTypePass(),
            new QueryFieldPass(),
            new ConnectionFieldPass(),
            new MutationFieldPass(),
        ];

        // Higher priority runs first; reverse so $passes reads in execution order.
        foreach (array_reverse($passes) as $priority => $pass) {
            $container->addCompilerPass($pass, PassConfig::TYPE_BEFORE_OPTIMIZATION, $priority);
        }
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return new GraphQLExtension();
    }
}
