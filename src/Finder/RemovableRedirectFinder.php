<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Finder;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolverInterface;

final class RemovableRedirectFinder implements RemovableRedirectFinderInterface
{
    private RedirectionPathResolverInterface $redirectionPathResolver;

    public function __construct(RedirectionPathResolverInterface $redirectionPathResolver)
    {
        $this->redirectionPathResolver = $redirectionPathResolver;
    }

    public function findRedirectsTargetedBy(RedirectInterface $redirect): Collection
    {
        /** @var ArrayCollection<int, RedirectInterface> $result */
        $result = new ArrayCollection();

        if ($redirect->getChannels()->isEmpty()) {
            $redirectionPath = $this->redirectionPathResolver->resolve((string) $redirect->getDestination());
            $firstRedirect = $redirectionPath->first();
            if (null !== $firstRedirect && !$result->contains($firstRedirect)) {
                $result->add($firstRedirect);
            }
        } else {
            foreach ($redirect->getChannels() as $channel) {
                $redirectionPath = $this->redirectionPathResolver->resolve((string) $redirect->getDestination(), $channel);
                $firstRedirect = $redirectionPath->first();
                if (null !== $firstRedirect && !$result->contains($firstRedirect)) {
                    $result->add($firstRedirect);
                }
            }
        }

        return $result;
    }
}
