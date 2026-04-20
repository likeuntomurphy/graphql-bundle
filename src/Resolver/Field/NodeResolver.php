<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Resolver\Field;

use Likeuntomurphy\GraphQL\GlobalObjectManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Contracts\Service\ServiceProviderInterface;

class NodeResolver
{
    /** @param ServiceProviderInterface<GlobalObjectManagerInterface> $managers */
    public function __construct(
        private NodeIdResolver $nodeIdResolver,
        #[AutowireLocator(GlobalObjectManagerInterface::TAG, indexAttribute: 'key')] private ServiceProviderInterface $managers,
    ) {
    }

    /** @param array<string, mixed> $args */
    public function __invoke(mixed $source, array $args): ?object
    {
        /** @var string $encodedId */
        $encodedId = $args['id'];
        $nodeId = $this->nodeIdResolver->decode($encodedId);

        return $this->managers->get($nodeId->getTypeName())->read($nodeId->getId());
    }
}
