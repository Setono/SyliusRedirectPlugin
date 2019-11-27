<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Decider;

interface AutomaticRedirectCreationDeciderInterface
{
    public function askAutomaticRedirectCreation(object $object): void;

    public function isAutomaticRedirectCreationAsked(object $object): bool;
}
