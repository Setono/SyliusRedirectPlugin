<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\SlugUpdateHandler;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusRedirectPlugin\Exception\SlugUpdateHandlerValidationException;
use Setono\SyliusRedirectPlugin\Factory\RedirectFactoryInterface;
use Setono\SyliusRedirectPlugin\Finder\RemovableRedirectFinderInterface;
use Sylius\Component\Channel\Model\ChannelAwareInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Channel\Model\ChannelsAwareInterface;
use Sylius\Component\Resource\Model\TranslationInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class SlugUpdateHandler implements SlugUpdateHandlerInterface
{
    protected RedirectFactoryInterface $redirectFactory;

    protected EntityManagerInterface $redirectManager;

    protected UrlGeneratorInterface $urlGenerator;

    protected RemovableRedirectFinderInterface $removableRedirectFinder;

    protected ValidatorInterface $validator;

    /** @var list<string> */
    protected array $validationGroups;

    /**
     * @param list<string> $validationGroups
     */
    public function __construct(
        RedirectFactoryInterface $redirectFactory,
        EntityManagerInterface $redirectManager,
        UrlGeneratorInterface $urlGenerator,
        RemovableRedirectFinderInterface $removableRedirectFinder,
        ValidatorInterface $validator,
        array $validationGroups
    ) {
        $this->redirectFactory = $redirectFactory;
        $this->redirectManager = $redirectManager;
        $this->urlGenerator = $urlGenerator;
        $this->removableRedirectFinder = $removableRedirectFinder;
        $this->validator = $validator;
        $this->validationGroups = $validationGroups;
    }

    public function handle(SlugUpdateHandlerCommand $slugUpdateHandlerCommand): void
    {
        if ($slugUpdateHandlerCommand->getOldSlug() === $slugUpdateHandlerCommand->getNewSlug()) {
            return;
        }

        $obj = $slugUpdateHandlerCommand->getObject();

        // todo right now this handler only supports objects of this type since they have a locale
        if (!$obj instanceof TranslationInterface) {
            return;
        }

        $locale = $obj->getLocale();
        if (null === $locale) {
            return;
        }

        $oldUrl = $this->generateUrl($slugUpdateHandlerCommand->getOldSlug(), $locale);
        $newUrl = $this->generateUrl($slugUpdateHandlerCommand->getNewSlug(), $locale);

        /** @var list<ChannelInterface> $channels */
        $channels = [];

        if ($obj instanceof ChannelAwareInterface) {
            $channel = $obj->getChannel();
            if (null !== $channel) {
                $channels[] = $channel;
            }
        }

        if ($obj instanceof ChannelsAwareInterface) {
            foreach ($obj->getChannels() as $channel) {
                $channels[] = $channel;
            }
        }

        $redirect = $this->redirectFactory->createNewWithValues($oldUrl, $newUrl, true, false, $channels);

        $removableRedirects = $this->removableRedirectFinder->findRedirectsTargetedBy($redirect);
        foreach ($removableRedirects as $removableRedirect) {
            $this->redirectManager->remove($removableRedirect);
        }

        $violations = $this->validator->validate($redirect, null, $this->validationGroups);
        if (count($violations) > 0) {
            throw new SlugUpdateHandlerValidationException($violations);
        }

        $this->redirectManager->persist($redirect);
    }

    abstract protected function generateUrl(string $slug, string $locale): string;
}
