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

    /**
     * Is called when the redirect source path is accessed
     */
    public function onAccess(): void;
    
    /**
     * @return bool
     */
    public function isEnabled(): bool;
    
    /**
     * @param bool $enabled
     */
    public function setEnabled(?bool $enabled): void;
    
    public function enable(): void;
    
    public function disable(): void;
    
    /**
     * @return bool
     */
    public function isRelative(): bool;
    
    /**
     * @param bool $relative
     */
    public function setRelative(bool $relative): void;
    
    /**
     * @return bool
     */
    public function isRedirectFound(): bool;
    
    /**
     * @param bool $redirectFound
     */
    public function setRedirectFound(bool $redirectFound): void;
}
