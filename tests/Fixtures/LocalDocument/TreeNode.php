<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Tests\Fixtures\LocalDocument;

class TreeNode
{
    public string $name;
    public TreeNode $child;
}
