<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Model;

use DateTimeInterface;
use Safe\DateTime;
use Sylius\Component\Resource\Model\TimestampableTrait;
use Symfony\Component\HttpFoundation\Request;

class NotFound implements NotFoundInterface
{
    use TimestampableTrait;

    /** @var int */
    protected $id;

    /** @var string */
    protected $url;

    /** @var int */
    protected $count = 0;

    /** @var bool */
    protected $ignored = false;

    /** @var DateTimeInterface */
    protected $lastRequestAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): void
    {
        $this->count = $count;
    }

    public function isIgnored(): bool
    {
        return $this->ignored;
    }

    public function setIgnored(bool $ignored): void
    {
        $this->ignored = $ignored;
    }

    public function getLastRequestAt(): ?DateTimeInterface
    {
        return $this->lastRequestAt;
    }

    public function setLastRequestAt(DateTimeInterface $lastRequestAt): void
    {
        $this->lastRequestAt = $lastRequestAt;
    }

    public function onRequest(Request $request): void
    {
        ++$this->count;
        $this->lastRequestAt = new DateTime();
        $this->url = $request->getUri();
    }
}
