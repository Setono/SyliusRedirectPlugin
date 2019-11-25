<?php

declare(strict_types=1);

namespace spec\Setono\SyliusRedirectPlugin\Decider;

use PhpSpec\ObjectBehavior;
use Setono\SyliusRedirectPlugin\Decider\AutomaticRedirectCreationDeciderInterface;
use Sylius\Component\Core\Model\ProductTranslation;

class AutomaticRedirectCreationDeciderSpec extends ObjectBehavior
{
    public function it_implements_automatic_redirect_creation_decider_interface(): void
    {
        $this->shouldImplement(AutomaticRedirectCreationDeciderInterface::class);
    }

    public function it_stores_automatic_redirect_creation_for_object(): void
    {
        $item = new ProductTranslation();
        $this->askAutomaticRedirectCreation($item);
        $this->isAutomaticRedirectCreationAsked($item)->shouldReturn(true);
    }

    public function it_returns_false_by_default(): void
    {
        $item = new ProductTranslation();
        $this->isAutomaticRedirectCreationAsked($item)->shouldReturn(false);
    }

    public function it_does_not_mix_objects_stored(): void
    {
        $item = new ProductTranslation();
        $this->askAutomaticRedirectCreation($item);
        $item2 = new ProductTranslation();
        $this->isAutomaticRedirectCreationAsked($item)->shouldReturn(true);
        $this->isAutomaticRedirectCreationAsked($item2)->shouldReturn(false);
    }
}
