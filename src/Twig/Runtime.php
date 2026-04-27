<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Twig;

use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Twig\Extension\RuntimeExtensionInterface;

final readonly class Runtime implements RuntimeExtensionInterface
{
    public function __construct(private ChannelRepositoryInterface $channelRepository)
    {
    }

    /** @return list<string> */
    public function getEnabledChannelHostnames(): array
    {
        $hostnames = [];
        foreach ($this->channelRepository->findBy(['enabled' => true]) as $channel) {
            if (!$channel instanceof ChannelInterface) {
                continue;
            }
            $hostname = $channel->getHostname();
            if (null !== $hostname && '' !== $hostname) {
                $hostnames[] = $hostname;
            }
        }

        return $hostnames;
    }
}
