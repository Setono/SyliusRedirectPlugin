<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\SlugUpdateHandler;

final class SlugUpdateHandlerCommand
{
    private object $object;

    private string $oldSlug;

    private string $newSlug;

    public function __construct(object $object, string $oldSlug, string $newSlug)
    {
        $this->object = $object;
        $this->oldSlug = $oldSlug;
        $this->newSlug = $newSlug;
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
