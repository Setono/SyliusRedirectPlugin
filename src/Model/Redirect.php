<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Model;

use DateTime;
use DateTimeInterface;
use Sylius\Component\Resource\Model\ToggleableTrait;

class Redirect implements RedirectInterface
{
    use ToggleableTrait;

    /** @var int */
    private $id;

    /** @var string */
    private $source;

    /** @var string */
    private $destination;

    /** @var bool */
    private $permanent = true;

    /** @var int */
    private $count = 0;

    /** @var DateTimeInterface|null */
    private $lastAccessed;

    /** @var bool */
    private $only404 = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): void
    {
        $this->destination = $destination;
    }

    public function isPermanent(): bool
    {
        return $this->permanent;
    }

    public function setPermanent(bool $permanent): void
    {
        $this->permanent = $permanent;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): void
    {
        $this->count = $count;
    }

    public function getLastAccessed(): ?DateTimeInterface
    {
        return $this->lastAccessed;
    }

    public function setLastAccessed(DateTimeInterface $lastAccessed): void
    {
        $this->lastAccessed = $lastAccessed;
    }

    public function onAccess(): void
    {
        ++$this->count;
        $this->setLastAccessed(new DateTime());
    }

    public function isOnly404(): bool
    {
        return $this->only404;
    }

    public function setOnly404(bool $only404): void
    {
        $this->only404 = $only404;
    }
}
