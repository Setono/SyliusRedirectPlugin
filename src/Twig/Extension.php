<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class Extension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'setono_sylius_redirect_enabled_channel_hostnames',
                [Runtime::class, 'getEnabledChannelHostnames'],
            ),
        ];
    }
}
