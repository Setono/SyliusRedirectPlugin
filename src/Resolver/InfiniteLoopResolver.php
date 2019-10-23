<?php

declare(strict_types = 1);

namespace Setono\SyliusRedirectPlugin\Resolver;

use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;

final class InfiniteLoopResolver implements InfiniteLoopResolverInterface
{
    private $redirectRepository;

    public function __construct(RedirectRepositoryInterface $redirectRepository)
    {
        $this->redirectRepository = $redirectRepository;
    }

    public function generatesInfiniteLoop(RedirectInterface $redirect): bool
    {
        return $this->getConflictingRedirect($redirect) instanceof RedirectInterface;
    }

    public function getConflictingRedirect(RedirectInterface $redirect): ?RedirectInterface
    {
        $nextRedirect = $this->redirectRepository->searchNextRedirect($redirect);
        while ($nextRedirect instanceof RedirectInterface) {
            if ($nextRedirect->getDestination() === $redirect->getSource()) {
                return $nextRedirect;
            }
            $nextRedirect = $this->redirectRepository->searchNextRedirect($nextRedirect);
        }

        return null;
    }
}