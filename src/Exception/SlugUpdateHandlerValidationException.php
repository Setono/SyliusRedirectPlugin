<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

final class SlugUpdateHandlerValidationException extends SlugUpdateHandlerException
{
    /** @var ConstraintViolationListInterface */
    private $constraintViolationList;

    public function __construct(ConstraintViolationListInterface $constraintViolationList)
    {
        parent::__construct('A validation constraint failed when trying to handle the slug update');

        $this->constraintViolationList = $constraintViolationList;
    }

    public function getConstraintViolationList(): ConstraintViolationListInterface
    {
        return $this->constraintViolationList;
    }
}
