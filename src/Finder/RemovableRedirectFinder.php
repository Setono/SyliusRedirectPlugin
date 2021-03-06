<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Finder;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolverInterface;

final class RemovableRedirectFinder implements RemovableRedirectFinderInterface
{
    /** @var RedirectionPathResolverInterface */
    private $redirectionPathResolver;

    public function __construct(RedirectionPathResolverInterface $redirectionPathResolver)
    {
        $this->redirectionPathResolver = $redirectionPathResolver;
    }

    public function findRedirectsTargetedBy(RedirectInterface $redirect): Collection
    {
        $result = new ArrayCollection();

        if ($redirect->getChannels()->isEmpty()) {
            $redirectionPath = $this->redirectionPathResolver->resolve((string) $redirect->getDestination());
            if (!$redirectionPath->isEmpty() && !$result->contains($redirectionPath->first())) {
                $result->add($redirectionPath->first());
            }
        } else {
            foreach ($redirect->getChannels() as $channel) {
                $redirectionPath = $this->redirectionPathResolver->resolve((string) $redirect->getDestination(), $channel);
                if (!$redirectionPath->isEmpty() && !$result->contains($redirectionPath->first())) {
                    $result->add($redirectionPath->first());
                }
            }
        }

        return $result;
    }
}
