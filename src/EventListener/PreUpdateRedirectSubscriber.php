<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Setono\SyliusRedirectPlugin\HashGenerator\HashGeneratorInterface;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PreUpdateRedirectSubscriber implements EventSubscriberInterface
{
    /**
     * @var HashGeneratorInterface
     */
    private $hashGenerator;

    public function __construct(HashGeneratorInterface $hashGenerator)
    {
        $this->hashGenerator = $hashGenerator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'setono_sylius_redirect.redirect.pre_create' => 'update',
            'setono_sylius_redirect.redirect.pre_update' => 'update',
        ];
    }

    public function update(ResourceControllerEvent $event): void
    {
        $redirect = $event->getSubject();

        if (!($redirect instanceof RedirectInterface)) {
            return;
        }

        $redirect->setSourceHash($this->hashGenerator->hash($redirect->getSource()));
    }
}
