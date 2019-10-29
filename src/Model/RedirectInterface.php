<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Model;

use DateTimeInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

interface RedirectInterface extends ResourceInterface
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
     * Is called when the redirect source path is accessed
     */
    public function onAccess(): void;

    public function isEnabled(): bool;

    /**
     * @param bool $enabled
     */
    public function setEnabled(?bool $enabled): void;

    public function enable(): void;

    public function disable(): void;

    public function isOnly404(): bool;

    public function setOnly404(bool $only404): void;
}
