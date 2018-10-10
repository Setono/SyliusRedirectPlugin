<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
    /**
     * @param MenuBuilderEvent $event
     */
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $child = $menu->getChild('configuration');
        if ($child !== null) {
            $child->addChild('redirects', [
                'route' => 'setono_sylius_redirect_admin_redirect_index',
            ])
                ->setLabel('setono_sylius_redirect.menu.admin.main.configuration.redirects')
                ->setLabelAttribute('icon', 'arrow alternate circle right outline')
            ;
        }
    }
}
