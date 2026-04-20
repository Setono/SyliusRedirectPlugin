<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

final class SlugUpdateHandlerValidationException extends SlugUpdateHandlerException
{
    public function __construct(private readonly ConstraintViolationListInterface $constraintViolationList)
    {
        parent::__construct('A validation constraint failed when trying to handle the slug update');
    }

    public function getConstraintViolationList(): ConstraintViolationListInterface
    {
        return $this->constraintViolationList;
    }
}
