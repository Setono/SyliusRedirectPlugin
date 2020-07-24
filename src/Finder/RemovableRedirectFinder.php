<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Finder;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolverInterface;
use Webmozart\Assert\Assert;

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

        $destination = $redirect->getDestination();
        Assert::notNull($destination);
        if ($redirect->getChannels()->isEmpty()) {
            $redirectionPath = $this->redirectionPathResolver->resolve($destination);
            if (!$redirectionPath->isEmpty() && !$result->contains($redirectionPath->first())) {
                $result->add($redirectionPath->first());
            }
        } else {
            foreach ($redirect->getChannels() as $channel) {
                $redirectionPath = $this->redirectionPathResolver->resolve($destination, $channel);
                if (!$redirectionPath->isEmpty() && !$result->contains($redirectionPath->first())) {
                    $result->add($redirectionPath->first());
                }
            }
        }

        return $result;
    }
}
