<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Twig\EventSubscriber;

use Sylius\Bundle\AdminBundle\Twig\Component\Taxon\FormComponent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;

final readonly class TaxonFormComponentSubscriber implements EventSubscriberInterface
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
        if (($request === null) || !str_ends_with($request->getRequestUri(), '/generateTaxonSlug')) {
            return;
        }

        $data = json_decode((string) $request->request->get('data', ''), true);
        if (!is_array($data) || !isset($data['args']) || !is_array($data['args'])) {
            return;
        }

        $localeCode = $data['args']['localeCode'] ?? '';
        if (!is_string($localeCode) || $localeCode === '') {
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

        /** @var array<string, mixed> $formVars */
        $formVars = $addAutomaticRedirect->vars;
        $attr = $formVars['attr'] ?? [];
        if (!is_array($attr)) {
            return;
        }
        $attr['show'] = true;
        $formVars['attr'] = $attr;
        $addAutomaticRedirect->vars = $formVars;
    }
}
