<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventSubscriber;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AdminMenuSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.menu.admin.main' => 'addAdminMenuItems',
        ];
    }

    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $event->getMenu()->getChild('configuration')?->addChild('redirects', [
            'route' => 'setono_sylius_redirect_admin_redirect_index',
        ])
            ->setLabel('setono_sylius_redirect.menu.admin.main.configuration.redirects')
            ->setLabelAttribute('icon', 'arrow alternate circle right outline')
        ;
    }
}
