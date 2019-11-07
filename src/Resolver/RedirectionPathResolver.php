<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Resolver;

use Safe\Exceptions\StringsException;
use Setono\SyliusRedirectPlugin\Exception\InfiniteLoopException;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Model\RedirectionPath;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Symfony\Component\HttpFoundation\Request;

final class RedirectionPathResolver implements RedirectionPathResolverInterface
{
    /** @var RedirectRepositoryInterface */
    private $redirectRepository;

    public function __construct(RedirectRepositoryInterface $redirectRepository)
    {
        $this->redirectRepository = $redirectRepository;
    }

    /**
     * @throws StringsException
     */
    public function resolve(string $source,
                            ?ChannelInterface $channel = null,
                            bool $only404 = false
    ): RedirectionPath {
        $redirectionPath = new RedirectionPath();

        /** @var RedirectInterface|null $redirect */
        $redirect = null;

        do {
            $redirect = $this->redirectRepository->findOneEnabledBySource($source, $channel, $only404);

            if (null !== $redirect) {
                $redirectionPath->addRedirect($redirect);
                $source = (string) $redirect->getDestination();
            }

            if ($redirectionPath->hasCycle()) {
                throw new InfiniteLoopException(
                    $redirectionPath->first() !== null ? $redirectionPath->first()->getSource() : $source
                );
            }
        } while (null !== $redirect && !$redirect->isOnly404()); // See this issue for explanation of this: https://github.com/Setono/SyliusRedirectPlugin/issues/27

        return $redirectionPath;
    }

    /**
     * @throws StringsException
     */
    public function resolveFromRequest(Request $request, ?ChannelInterface $channel = null, bool $only404 = false): RedirectionPath
    {
        return $this->resolve($request->getPathInfo(), $channel, $only404);
    }
}
