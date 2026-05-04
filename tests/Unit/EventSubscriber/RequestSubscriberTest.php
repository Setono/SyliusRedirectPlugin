<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Unit\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Setono\SyliusRedirectPlugin\EventSubscriber\RequestSubscriber;
use Setono\SyliusRedirectPlugin\Model\Redirect;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Model\RedirectionPath;
use Setono\SyliusRedirectPlugin\Resolver\RedirectionPathResolverInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Channel\Model\Channel;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class RequestSubscriberTest extends TestCase
{
    use ProphecyTrait;

    /** @var ObjectProphecy<ManagerRegistry> */
    private ObjectProphecy $managerRegistry;

    /** @var ObjectProphecy<ChannelContextInterface> */
    private ObjectProphecy $channelContext;

    /** @var ObjectProphecy<RedirectionPathResolverInterface> */
    private ObjectProphecy $resolver;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->prophesize(ManagerRegistry::class);
        $this->channelContext = $this->prophesize(ChannelContextInterface::class);
        $this->resolver = $this->prophesize(RedirectionPathResolverInterface::class);
    }

    public function test_subscribes_to_kernel_request_at_priority_31(): void
    {
        self::assertSame(
            ['kernel.request' => ['onKernelRequest', 31]],
            RequestSubscriber::getSubscribedEvents(),
        );
    }

    public function test_it_does_nothing_for_sub_requests(): void
    {
        $this->resolver->resolveFromRequest(Argument::cetera())->shouldNotBeCalled();

        $event = $this->createEvent(Request::create('/foo'), HttpKernelInterface::SUB_REQUEST);

        $this->createSubscriber()->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function test_it_does_nothing_when_no_redirect_matches(): void
    {
        $this->channelContext->getChannel()->willThrow(new ChannelNotFoundException());
        $this->resolver
            ->resolveFromRequest(Argument::type(Request::class), null, false)
            ->willReturn(new RedirectionPath());

        $event = $this->createEvent(Request::create('/foo'));

        $this->createSubscriber()->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function test_it_swallows_a_channel_not_found_exception_and_resolves_anyway(): void
    {
        $this->channelContext->getChannel()->willThrow(new ChannelNotFoundException());
        $this->resolver
            ->resolveFromRequest(Argument::type(Request::class), null, false)
            ->willReturn(new RedirectionPath())
            ->shouldBeCalled();

        $event = $this->createEvent(Request::create('/foo'));

        $this->createSubscriber()->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function test_it_passes_the_resolved_channel_to_the_resolver(): void
    {
        $channel = new Channel();
        $this->channelContext->getChannel()->willReturn($channel);
        $this->resolver
            ->resolveFromRequest(Argument::type(Request::class), $channel, false)
            ->willReturn(new RedirectionPath())
            ->shouldBeCalled();

        $event = $this->createEvent(Request::create('/foo'));

        $this->createSubscriber()->onKernelRequest($event);
    }

    public function test_it_returns_a_permanent_redirect_for_a_non_empty_path(): void
    {
        $redirect = $this->createRedirect('/dest', permanent: true, keepQueryString: false);
        $path = new RedirectionPath();
        $path->addRedirect($redirect);

        $this->channelContext->getChannel()->willThrow(new ChannelNotFoundException());
        $this->resolver->resolveFromRequest(Argument::cetera())->willReturn($path);

        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->flush()->shouldBeCalled();
        $this->managerRegistry->getManagerForClass(Redirect::class)->willReturn($manager->reveal());

        $event = $this->createEvent(Request::create('/source'));

        $this->createSubscriber()->onKernelRequest($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/dest', $response->getTargetUrl());
        self::assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
    }

    public function test_it_returns_a_temporary_redirect_when_the_redirect_is_not_permanent(): void
    {
        $redirect = $this->createRedirect('/dest', permanent: false, keepQueryString: false);
        $path = new RedirectionPath();
        $path->addRedirect($redirect);

        $this->channelContext->getChannel()->willThrow(new ChannelNotFoundException());
        $this->resolver->resolveFromRequest(Argument::cetera())->willReturn($path);

        $manager = $this->prophesize(EntityManagerInterface::class);
        $this->managerRegistry->getManagerForClass(Redirect::class)->willReturn($manager->reveal());

        $event = $this->createEvent(Request::create('/source'));

        $this->createSubscriber()->onKernelRequest($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
    }

    public function test_it_appends_the_query_string_when_keep_query_string_is_true(): void
    {
        $redirect = $this->createRedirect('/dest', permanent: true, keepQueryString: true);
        $path = new RedirectionPath();
        $path->addRedirect($redirect);

        $this->channelContext->getChannel()->willThrow(new ChannelNotFoundException());
        $this->resolver->resolveFromRequest(Argument::cetera())->willReturn($path);

        $manager = $this->prophesize(EntityManagerInterface::class);
        $this->managerRegistry->getManagerForClass(Redirect::class)->willReturn($manager->reveal());

        $event = $this->createEvent(Request::create('/source?utm_source=foo&page=2'));

        $this->createSubscriber()->onKernelRequest($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertStringContainsString('utm_source=foo', $response->getTargetUrl());
        self::assertStringContainsString('page=2', $response->getTargetUrl());
    }

    public function test_it_logs_and_skips_when_the_destination_loops_back_to_the_request_path(): void
    {
        $redirect = $this->createRedirect('/source', permanent: true, keepQueryString: false);
        $path = new RedirectionPath();
        $path->addRedirect($redirect);

        $this->channelContext->getChannel()->willThrow(new ChannelNotFoundException());
        $this->resolver->resolveFromRequest(Argument::cetera())->willReturn($path);

        $manager = $this->prophesize(EntityManagerInterface::class);
        $this->managerRegistry->getManagerForClass(Redirect::class)->willReturn($manager->reveal());

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error('Infinite loop detected', Argument::type('array'))->shouldBeCalled();

        $subscriber = $this->createSubscriber();
        $subscriber->setLogger($logger->reveal());

        $event = $this->createEvent(Request::create('/source'));

        $subscriber->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function test_it_marks_every_redirect_in_the_path_as_accessed(): void
    {
        $first = $this->createRedirect('/middle', permanent: true, keepQueryString: false);
        $last = $this->createRedirect('/dest', permanent: true, keepQueryString: false);

        $path = new RedirectionPath();
        $path->addRedirect($first);
        $path->addRedirect($last);

        $this->channelContext->getChannel()->willThrow(new ChannelNotFoundException());
        $this->resolver->resolveFromRequest(Argument::cetera())->willReturn($path);

        $manager = $this->prophesize(EntityManagerInterface::class);
        $manager->flush()->shouldBeCalledOnce();
        $this->managerRegistry->getManagerForClass(Redirect::class)->willReturn($manager->reveal());

        $event = $this->createEvent(Request::create('/source'));

        $this->createSubscriber()->onKernelRequest($event);

        self::assertSame(1, $first->getCount());
        self::assertSame(1, $last->getCount());
        self::assertNotNull($first->getLastAccessed());
        self::assertNotNull($last->getLastAccessed());
    }

    private function createSubscriber(): RequestSubscriber
    {
        return new RequestSubscriber(
            $this->managerRegistry->reveal(),
            $this->channelContext->reveal(),
            $this->resolver->reveal(),
        );
    }

    private function createEvent(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent($this->prophesize(HttpKernelInterface::class)->reveal(), $request, $type);
    }

    private function createRedirect(string $destination, bool $permanent, bool $keepQueryString): RedirectInterface
    {
        $redirect = new Redirect();
        $redirect->setSource('/source');
        $redirect->setDestination($destination);
        $redirect->setPermanent($permanent);
        $redirect->setKeepQueryString($keepQueryString);

        return $redirect;
    }
}
