<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Resolver;

use Setono\SyliusRedirectPlugin\Exception\InfiniteLoopException;
use Setono\SyliusRedirectPlugin\Model\RedirectionPath;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Symfony\Component\HttpFoundation\Request;

final class RedirectionPathResolver implements RedirectionPathResolverInterface
{
    private RedirectRepositoryInterface $redirectRepository;

    public function __construct(RedirectRepositoryInterface $redirectRepository)
    {
        $this->redirectRepository = $redirectRepository;
    }

    public function resolve(
        string $source,
        ChannelInterface $channel = null,
        bool $only404 = false
    ): RedirectionPath {
        $redirectionPath = new RedirectionPath();

        do {
            $redirect = $this->redirectRepository->findOneEnabledBySource($source, $channel, $only404);

            if (null !== $redirect) {
                $redirectionPath->addRedirect($redirect);
                $source = (string) $redirect->getDestination();
            }

            /** @psalm-suppress TypeDoesNotContainType */
            if ($redirectionPath->hasCycle()) {
                $firstRedirect = $redirectionPath->first();

                throw new InfiniteLoopException(
                    null !== $firstRedirect ? ($firstRedirect->getSource() ?? $source) : $source
                );
            }
        } while (null !== $redirect && !$redirect->isOnly404()); // See this issue for explanation of this: https://github.com/Setono/SyliusRedirectPlugin/issues/27

        return $redirectionPath;
    }

    public function resolveFromRequest(
        Request $request,
        ChannelInterface $channel = null,
        bool $only404 = false
    ): RedirectionPath {
        return $this->resolve($request->getPathInfo(), $channel, $only404);
    }
}
