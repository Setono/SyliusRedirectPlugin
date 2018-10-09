<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;

interface RedirectInterface extends ResourceInterface
{
    /**
     * @return int
     */
    public function getId(): ?int;

    /**
     * @return string
     */
    public function getSource(): ?string;

    /**
     * @param string $source
     */
    public function setSource(string $source): void;

    /**
     * @return string
     */
    public function getSourceHash(): ?string;

    /**
     * @param string $sourceHash
     */
    public function setSourceHash(string $sourceHash): void;

    /**
     * @return string
     */
    public function getDestination(): ?string;

    /**
     * @param string $destination
     */
    public function setDestination(string $destination): void;

    /**
     * @return bool
     */
    public function isPermanent(): bool;

    /**
     * @param bool $permanent
     */
    public function setPermanent(bool $permanent): void;

    /**
     * @return int
     */
    public function getCount(): int;

    /**
     * @param int $count
     */
    public function setCount(int $count): void;

    /**
     * @return \DateTimeInterface|null
     */
    public function getLastAccessed(): ?\DateTimeInterface;

    /**
     * @param \DateTimeInterface $lastAccessed
     */
    public function setLastAccessed(\DateTimeInterface $lastAccessed): void;
}
