<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Decider;

final class AutomaticRedirectCreationDecider implements AutomaticRedirectCreationDeciderInterface
{
    /** @var array */
    private $config = [];

    public function askAutomaticRedirectCreation(object $object): void
    {
        $index = $this->getObjectIndex($object);

        $this->config[$index] = true;
    }

    public function isAutomaticRedirectCreationAsked(object $object): bool
    {
        $index = $this->getObjectIndex($object);

        if (!isset($this->config[$index])) {
            return false;
        }

        return $this->config[$index];
    }

    private function getObjectIndex(object $object): string
    {
        return \spl_object_hash($object);
    }
}
