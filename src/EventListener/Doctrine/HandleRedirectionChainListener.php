<?php
declare(strict_types=1);


namespace Setono\SyliusRedirectPlugin\EventListener\Doctrine;


use Doctrine\Persistence\Event\LifecycleEventArgs;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;

/**
 * Let us outline the different scenarios that this service should handle:
 *
 * Scenario 1: Global redirect
 * ---
 * We have an existing redirect going from A => B,
 * and now we add a new redirect going from B => C
 */
final class HandleRedirectionChainListener
{
    private RedirectRepositoryInterface $redirectRepository;

    public function __construct(RedirectRepositoryInterface $redirectRepository)
    {
        $this->redirectRepository = $redirectRepository;
    }

    public function prePersist(LifecycleEventArgs $lifecycleEventArgs): void
    {
        $this->handle($lifecycleEventArgs);
    }

    public function preUpdate(LifecycleEventArgs $lifecycleEventArgs): void
    {
        $this->handle($lifecycleEventArgs);
    }

    private function handle(LifecycleEventArgs $lifecycleEventArgs): void
    {
        $redirect = $lifecycleEventArgs->getObject();
        if (!$redirect instanceof RedirectInterface) {
            return;
        }

        $source = $redirect->getSource();
        if(null === $source) {
            return;
        }

        // find all redirects where the destination equals $source
        // and update those redirects with the destination of $redirect
    }
}
