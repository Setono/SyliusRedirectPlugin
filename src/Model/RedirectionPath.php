<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Model;

use Countable;

final class RedirectionPath implements Countable
{
    /**
     * Array of seen redirect ids
     *
     * @var array
     */
    private $seen = [];

    /** @var RedirectInterface[] */
    private $redirects = [];

    /** @var bool */
    private $cycle = false;

    public function addRedirect(RedirectInterface $redirect): void
    {
        if (isset($this->seen[$redirect->getId()])) {
            $this->cycle = true;
        }

        $this->redirects[] = $redirect;
        $this->seen[$redirect->getId()] = true;
    }

    /**
     * Will mark all redirects in this path as accessed
     */
    public function markAsAccessed(): void
    {
        foreach ($this->redirects as $redirect) {
            $redirect->onAccess();
        }
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * @return RedirectInterface[]
     */
    public function all(): array
    {
        return $this->redirects;
    }

    public function first(): ?RedirectInterface
    {
        return $this->redirects[0] ?? null;
    }

    public function last(): ?RedirectInterface
    {
        if ($this->isEmpty()) {
            return null;
        }

        return $this->redirects[$this->count() - 1];
    }

    public function count(): int
    {
        return count($this->redirects);
    }

    /**
     * Returns true if the path has a cycle, i.e. an infinite loop
     */
    public function hasCycle(): bool
    {
        return $this->cycle;
    }
}
