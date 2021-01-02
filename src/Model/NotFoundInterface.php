<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Model;

use DateTimeInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;
use Symfony\Component\HttpFoundation\Request;

interface NotFoundInterface extends ResourceInterface, TimestampableInterface
{
    public function getId(): ?int;

    public function getUrl(): ?string;

    public function setUrl(string $url): void;

    public function getCount(): int;

    public function setCount(int $count): void;

    public function getLastRequestAt(): ?DateTimeInterface;

    public function setLastRequestAt(DateTimeInterface $lastRequestAt): void;

    /**
     * Is called when a 404 occurs in the application
     */
    public function onRequest(Request $request): void;
}
