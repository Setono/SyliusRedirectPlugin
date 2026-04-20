<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\SlugUpdateHandler;

final readonly class SlugUpdateHandlerCommand
{
    public function __construct(private object $object, private string $oldSlug, private string $newSlug)
    {
    }

    public function getObject(): object
    {
        return $this->object;
    }

    public function getOldSlug(): string
    {
        return $this->oldSlug;
    }

    public function getNewSlug(): string
    {
        return $this->newSlug;
    }
}
