<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL;

use GraphQL\Type\SchemaConfig;
use Likeuntomurphy\GraphQL\Resolver\Field\DefaultFieldResolverInterface;
use Likeuntomurphy\GraphQL\Type\Mutation;
use Likeuntomurphy\GraphQL\Type\Query;

class SchemaConfigBuilder
{
    public function __construct(
        private Query $query,
        private TypeRegistry $typeRegistry,
        private Mutation $mutation,
        private DefaultFieldResolverInterface $defaultFieldResolver,
    ) {
    }

    public function build(): SchemaConfig
    {
        $config = [
            'query' => $this->query,
            'typeLoader' => $this->typeRegistry->get(...),
            'fieldResolver' => $this->defaultFieldResolver,
        ];

        if (\count($this->mutation->getFields()) > 0) {
            $config['mutation'] = $this->mutation;
        }

        return SchemaConfig::create($config);
    }
}
