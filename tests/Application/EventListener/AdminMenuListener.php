<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Application\EventListener;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
    /** @var list<string> */
    private const ITEMS_TO_REMOVE = ['official_support', 'sylius.ui.administration'];

    /** @var list<string> */
    private const ITEMS_TO_COLLAPSE = ['sales', 'customers'];

    /** @var list<string> */
    private const ITEMS_TO_EXPAND = ['configuration'];

    public function __invoke(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        foreach (self::ITEMS_TO_REMOVE as $name) {
            if (null !== $menu->getChild($name)) {
                $menu->removeChild($name);
            }
        }

        foreach (self::ITEMS_TO_COLLAPSE as $name) {
            $menu->getChild($name)?->setExtra('always_open', false);
        }

        foreach (self::ITEMS_TO_EXPAND as $name) {
            $menu->getChild($name)?->setExtra('always_open', true);
        }
    }
}
