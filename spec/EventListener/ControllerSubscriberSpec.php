<?php

declare(strict_types=1);

namespace spec\Setono\SyliusRedirectPlugin\EventListener;

use Closure;
use Doctrine\Persistence\ObjectManager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Setono\SyliusRedirectPlugin\EventListener\ControllerSubscriber;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Model\RedirectionPath;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolverInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class ControllerSubscriberSpec extends ObjectBehavior
{
    public function let(
        ObjectManager $objectManager,
        ChannelContextInterface $channelContext,
        RedirectionPathResolverInterface $redirectionPathResolver,
        ChannelInterface $channel
    ): void {
        $channelContext->getChannel()->willReturn($channel);
        $redirectionPathResolver
            ->resolveFromRequest(Argument::type(Request::class), Argument::type(ChannelInterface::class), true)
            ->willReturn(new RedirectionPath())
        ;

        $this->beConstructedWith($objectManager, $channelContext, $redirectionPathResolver);
    }

    public function it_implements_event_subscriber_interface(): void
    {
        $this->shouldImplement(EventSubscriberInterface::class);
    }

    public function it_is_a_controller_listener(): void
    {
        $this->shouldHaveType(ControllerSubscriber::class);
    }

    public function it_redirects_to_the_correct_controller(
        ControllerEvent $filterControllerEvent,
        Request $request,
        ObjectManager $objectManager,
        RedirectionPathResolverInterface $redirectionPathResolver,
        RedirectInterface $redirect
    ): void {
        $filterControllerEvent->getRequest()->willReturn($request);
        $request->getPathInfo()->willReturn('/home');

        $redirectionPath = new RedirectionPath();
        $redirectionPath->addRedirect($redirect->getWrappedObject());

        $redirectionPathResolver
            ->resolveFromRequest(Argument::type(Request::class), Argument::type(ChannelInterface::class))
            ->willReturn($redirectionPath)
        ;

        $redirect->onAccess()->shouldBeCalled();
        $redirect->getDestination()->willReturn('/destination');
        $objectManager->flush()->shouldBeCalled();

        $filterControllerEvent->setController(Argument::type(Closure::class))->shouldBeCalled();

        $this->onKernelController($filterControllerEvent);
    }

    public function it_does_not_redirect_if_there_is_no_enabed_redirect(
        ControllerEvent $filterControllerEvent,
        Request $request,
        RedirectInterface $redirect
    ): void {
        $filterControllerEvent->getRequest()->willReturn($request);
        $request->getPathInfo()->willReturn('/home');

        $redirect->onAccess()->shouldNotBeCalled();
    }
}
