<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\SlugUpdateHandler;

interface SlugUpdateHandlerInterface
{
    public function handle(SlugUpdateHandlerCommand $slugUpdateHandlerCommand): void;
}
