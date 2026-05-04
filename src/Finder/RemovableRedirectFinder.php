<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Finder;

use Doctrine\Common\Collections\ArrayCollection;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolverInterface;

final readonly class RemovableRedirectFinder implements RemovableRedirectFinderInterface
{
    public function __construct(private RedirectionPathResolverInterface $redirectionPathResolver)
    {
    }

    public function findRedirectsTargetedBy(RedirectInterface $redirect): iterable
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
