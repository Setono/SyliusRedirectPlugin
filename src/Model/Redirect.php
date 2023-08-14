<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Model;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Model\TimestampableTrait;
use Sylius\Component\Resource\Model\ToggleableTrait;

class Redirect implements RedirectInterface
{
    use TimestampableTrait;

    use ToggleableTrait;

    protected ?int $id = null;

    protected ?string $source = null;

    protected ?string $destination = null;

    protected bool $permanent = true;

    protected int $count = 0;

    protected ?DateTimeInterface $lastAccessed = null;

    protected bool $only404 = true;

    protected bool $keepQueryString = false;

    /** @var Collection<array-key, ChannelInterface> */
    protected $channels;

    public function __construct()
    {
        $this->channels = new ArrayCollection();
    }

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

    public function getChannels(): Collection
    {
        return $this->channels;
    }

    public function addChannel(ChannelInterface $channel): void
    {
        if (!$this->hasChannel($channel)) {
            $this->channels->add($channel);
        }
    }

    public function removeChannel(ChannelInterface $channel): void
    {
        if ($this->hasChannel($channel)) {
            $this->channels->removeElement($channel);
        }
    }

    public function hasChannel(ChannelInterface $channel): bool
    {
        return $this->channels->contains($channel);
    }

    public function keepQueryString(): bool
    {
        return $this->keepQueryString;
    }

    public function setKeepQueryString(bool $keepQueryString): void
    {
        $this->keepQueryString = $keepQueryString;
    }
}
