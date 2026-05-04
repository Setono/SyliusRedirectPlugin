<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Model;

use DateTimeInterface;
use Sylius\Component\Channel\Model\ChannelsAwareInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;
use Sylius\Component\Resource\Model\ToggleableInterface;

interface RedirectInterface extends ResourceInterface, ToggleableInterface, ChannelsAwareInterface, TimestampableInterface
{
    public function getId(): ?int;

    public function getSource(): ?string;

    public function setSource(string $source): void;

    public function getDestination(): ?string;

    public function setDestination(string $destination): void;

    public function isPermanent(): bool;

    public function setPermanent(bool $permanent): void;

    public function getCount(): int;

    public function setCount(int $count): void;

    public function getLastAccessed(): ?DateTimeInterface;

    public function setLastAccessed(DateTimeInterface $lastAccessed): void;

    /**
     * Records a hit on this redirect: increments the access counter and stamps
     * `lastAccessed` with the current time.
     *
     * Called by the runtime each time the redirect is followed; downstream
     * tooling (the prune command, admin grid) reads `count` and `lastAccessed`
     * to identify stale or unused redirects.
     */
    public function markAsAccessed(): void;

    public function isEnabled(): bool;

    public function setEnabled(?bool $enabled): void;

    public function isOnly404(): bool;

    public function setOnly404(bool $only404): void;

    public function keepQueryString(): bool;

    public function setKeepQueryString(bool $keepQueryString): void;
}
