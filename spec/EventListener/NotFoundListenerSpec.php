<?php

declare(strict_types=1);

namespace spec\Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Setono\SyliusRedirectPlugin\EventListener\NotFoundListener;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class NotFoundListenerSpec extends ObjectBehavior
{
    function it_is_a_not_found_listener(): void
    {
        $this->shouldHaveType(NotFoundListener::class);
    }

    function let(
        RedirectRepositoryInterface $redirectRepository,
        ObjectManager $objectManager
    ): void {
        $this->beConstructedWith($redirectRepository, $objectManager);
    }

    function it_does_not_redirect_request_that_are_not_master_request(
        GetResponseForExceptionEvent $event
    ): void {
        $event->getRequestType()->willReturn(HttpKernelInterface::SUB_REQUEST);

        $event->getException()->shouldNotBeCalled();

        $event->setResponse(Argument::any())->shouldNotBeCalled();

        $this->onKernelException($event);
    }

    function it_does_not_redirect_successful_events(
        GetResponseForExceptionEvent $event,
        HttpException $exception
    ): void {
        $event->getRequestType()->willReturn(HttpKernelInterface::MASTER_REQUEST);

        $event->getException()->willReturn($exception);
        $exception->getStatusCode()->willReturn(500);

        $event->setResponse(Argument::any())->shouldNotBeCalled();

        $this->onKernelException($event);
    }

    function it_does_not_redirect_if_there_is_no_redirect_defined(
        GetResponseForExceptionEvent $event,
        HttpException $exception,
        RedirectRepositoryInterface $redirectRepository,
        Request $request
    ): void {
        $event->getRequestType()->willReturn(HttpKernelInterface::MASTER_REQUEST);

        $event->getException()->willReturn($exception);
        $exception->getStatusCode()->willReturn(Response::HTTP_NOT_FOUND);

        $event->getRequest()->willReturn($request);
        $request->getPathInfo()->willReturn('/404');

        $redirectRepository->findEnabledBySource('/404', true)->willReturn(null);

        $event->setResponse(Argument::any())->shouldNotBeCalled();

        $this->onKernelException($event);
    }

    function it_redirects_if_there_is_a_redirect(
        GetResponseForExceptionEvent $event,
        HttpException $exception,
        RedirectRepositoryInterface $redirectRepository,
        Request $request,
        ObjectManager $objectManager,
        RedirectInterface $redirect,
        RedirectInterface $lastRedirect
    ): void {
        $event->getRequestType()->willReturn(HttpKernelInterface::MASTER_REQUEST);

        $event->getException()->willReturn($exception);
        $exception->getStatusCode()->willReturn(Response::HTTP_NOT_FOUND);

        $event->getRequest()->willReturn($request);
        $request->getPathInfo()->willReturn('/404');

        $redirectRepository->findEnabledBySource('/404', true)->willReturn($redirect);

        $redirect->onAccess()->shouldBeCalled();
        $objectManager->flush()->shouldBeCalled();

        $redirectRepository->findLastRedirect($redirect, true)->willReturn($lastRedirect);
        $lastRedirect->getDestination()->willReturn('/404-de');
        $lastRedirect->isPermanent()->willReturn(true);

        $event->setResponse(Argument::type(RedirectResponse::class))->shouldBeCalled();

        $this->onKernelException($event);
    }
}
