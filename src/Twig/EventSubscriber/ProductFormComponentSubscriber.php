<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Twig\EventSubscriber;

use Sylius\Bundle\AdminBundle\Twig\Component\Product\FormComponent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;

final readonly class ProductFormComponentSubscriber implements EventSubscriberInterface
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [PreRenderEvent::class => 'onPreRender'];
    }

    public function onPreRender(PreRenderEvent $event): void
    {
        $component = $event->getComponent();
        if (!$component instanceof FormComponent) {
            return;
        }

        $request = $this->requestStack->getMainRequest();
        if (($request === null) || !str_ends_with($request->getRequestUri(), '/generateProductSlug')) {
            return;
        }

        $data = (array) json_decode((string) $request->request->get('data', ''), true);
        /** @var string $localeCode */
        $localeCode = $data['args']['localeCode'] ?? '';
        if ($localeCode === '') {
            return;
        }

        $vars = $event->getVariables();
        if (!isset($vars['form']) || !$vars['form'] instanceof FormView) {
            return;
        }

        $addAutomaticRedirect = $vars['form']->children['translations']->children[$localeCode]->children['addAutomaticRedirect'];
        if (!$addAutomaticRedirect instanceof FormView) {
            return;
        }

        /**
         * @psalm-suppress MixedOperand
         * @psalm-suppress MixedArrayAccess
         * @psalm-suppress MixedArrayAssignment
         */
        $addAutomaticRedirect->vars['attr'] += ['show' => true];
    }
}
