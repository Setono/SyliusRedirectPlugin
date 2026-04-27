<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Unit\Form\Type;

use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusRedirectPlugin\Form\Type\RedirectType;
use Setono\SyliusRedirectPlugin\Model\Redirect;
use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

final class RedirectTypeTest extends TypeTestCase
{
    use ProphecyTrait;

    public function test_it_synchronizes_submitted_data_into_a_redirect(): void
    {
        $form = $this->factory->create(RedirectType::class, new Redirect());

        $form->submit([
            'source' => '/old',
            'destination' => '/new',
            'permanent' => '1',
            'enabled' => '1',
            'only404' => '1',
            'keepQueryString' => '1',
            'channels' => [],
        ]);

        self::assertTrue($form->isSynchronized());

        /** @var Redirect $redirect */
        $redirect = $form->getData();
        self::assertSame('/old', $redirect->getSource());
        self::assertSame('/new', $redirect->getDestination());
        self::assertTrue($redirect->isPermanent());
        self::assertTrue($redirect->isEnabled());
        self::assertTrue($redirect->isOnly404());
        self::assertTrue($redirect->keepQueryString());
        self::assertCount(0, $redirect->getChannels());
    }

    public function test_unchecked_booleans_are_persisted_as_false(): void
    {
        $form = $this->factory->create(RedirectType::class, new Redirect());

        $form->submit([
            'source' => '/a',
            'destination' => '/b',
            'channels' => [],
        ]);

        self::assertTrue($form->isSynchronized());

        /** @var Redirect $redirect */
        $redirect = $form->getData();
        self::assertFalse($redirect->isPermanent());
        self::assertFalse($redirect->isEnabled());
        self::assertFalse($redirect->isOnly404());
        self::assertFalse($redirect->keepQueryString());
    }

    public function test_block_prefix_is_stable(): void
    {
        $type = new RedirectType(Redirect::class);
        self::assertSame('setono_sylius_redirect_redirect', $type->getBlockPrefix());
    }

    protected function getExtensions(): array
    {
        $type = new RedirectType(Redirect::class);

        $channelRepository = $this->prophesize(RepositoryInterface::class);
        $channelRepository->findAll()->willReturn([]);
        $channelChoice = new ChannelChoiceType($channelRepository->reveal());

        return [new PreloadedExtension([$type, $channelChoice], [])];
    }
}
