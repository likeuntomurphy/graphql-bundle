<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Http\ValueResolver;

use Likeuntomurphy\GraphQL\Pagination\CursorPaginationParams;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class CursorPaginationParamsValueResolver implements ValueResolverInterface
{
    public function __construct(
        #[Autowire(param: 'likeuntomurphy_graphql.pagination.limit')] private int $limit,
    ) {
    }

    /** @return iterable<CursorPaginationParams> */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (CursorPaginationParams::class !== $argument->getType()) {
            return [];
        }

        yield new CursorPaginationParams($this->limit)
            ->setFirst($request->query->getInt('first') ?: null)
            ->setAfter($request->query->getString('after') ?: null)
        ;
    }
}
