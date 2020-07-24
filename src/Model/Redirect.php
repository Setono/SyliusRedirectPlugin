<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Model;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Safe\DateTime;
use Sylius\Component\Channel\Model\ChannelInterface as BaseChannelInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Resource\Model\TimestampableTrait;
use Sylius\Component\Resource\Model\ToggleableTrait;

class Redirect implements RedirectInterface
{
    use TimestampableTrait;
    use ToggleableTrait;

    /** @var int */
    protected $id;

    /** @var string|null */
    protected $source;

    /** @var string|null */
    protected $destination;

    /** @var bool */
    protected $permanent = true;

    /** @var int */
    protected $count = 0;

    /** @var DateTimeInterface|null */
    protected $lastAccessed;

    /** @var bool */
    protected $only404 = true;

    /** @var Collection|ChannelInterface[] */
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

    public function setSource(?string $source): void
    {
        $this->source = $source;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(?string $destination): void
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

    public function addChannel(BaseChannelInterface $channel): void
    {
        if (!$this->hasChannel($channel)) {
            $this->channels->add($channel);
        }
    }

    public function removeChannel(BaseChannelInterface $channel): void
    {
        if ($this->hasChannel($channel)) {
            $this->channels->removeElement($channel);
        }
    }

    public function hasChannel(BaseChannelInterface $channel): bool
    {
        return $this->channels->contains($channel);
    }
}
