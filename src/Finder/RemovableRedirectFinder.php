<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Finder;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolverInterface;

final class RemovableRedirectFinder implements RemovableRedirectFinderInterface
{
    /** @var RedirectRepositoryInterface */
    private $redirectRepository;

    /** @var RedirectionPathResolverInterface */
    private $redirectionPathResolver;

    public function __construct(RedirectRepositoryInterface $redirectRepository, RedirectionPathResolverInterface $redirectionPathResolver)
    {
        $this->redirectRepository = $redirectRepository;
        $this->redirectionPathResolver = $redirectionPathResolver;
    }

    /**
     * Returns the redirects that have for source the destination of the given redirect
     *
     * If the redirect has multiple channels it does the operation for each channel
     *
     * @return Collection|RedirectInterface[]
     */
    public function findNextRedirect(RedirectInterface $redirect): Collection
    {
        $result = new ArrayCollection();

        if ($redirect->getChannels()->isEmpty()) {
            $redirectionPath = $this->redirectionPathResolver->resolve($redirect->getDestination());
            if (!$redirectionPath->isEmpty() && !$result->contains($redirectionPath->first())) {
                $result->add($redirectionPath->first());
            }
        } else {
            foreach ($redirect->getChannels() as $channel) {
                $redirectionPath = $this->redirectionPathResolver->resolve($redirect->getDestination(), $channel);
                if (!$redirectionPath->isEmpty() && !$result->contains($redirectionPath->first())) {
                    $result->add($redirectionPath->first());
                }
            }
        }

        return $result;
    }
}
