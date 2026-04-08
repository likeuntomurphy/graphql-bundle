<?php

declare(strict_types=1);

namespace Likeuntomurphy\GraphQL\Model;

use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

readonly class ValidationErrorList
{
    /** @param array<ValidationError> $errors */
    public function __construct(
        public array $errors,
    ) {
    }

    public static function fromConstraintViolationList(
        ConstraintViolationListInterface $violations,
    ): self {
        return new self(array_map(fn (ConstraintViolationInterface $violation) => new ValidationError($violation->getPropertyPath(), (string) $violation->getMessage()), iterator_to_array($violations)));
    }
}
