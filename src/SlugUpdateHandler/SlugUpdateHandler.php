<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\SlugUpdateHandler;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusRedirectPlugin\Exception\SlugUpdateHandlerValidationException;
use Setono\SyliusRedirectPlugin\Factory\RedirectFactoryInterface;
use Setono\SyliusRedirectPlugin\Finder\RemovableRedirectFinderInterface;
use Sylius\Component\Channel\Model\ChannelAwareInterface;
use Sylius\Component\Channel\Model\ChannelsAwareInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class SlugUpdateHandler implements SlugUpdateHandlerInterface
{
    /** @var RedirectFactoryInterface */
    private $redirectFactory;

    /** @var EntityManagerInterface */
    private $redirectManager;

    /** @var UrlGeneratorInterface */
    protected $urlGenerator;

    /** @var RemovableRedirectFinderInterface */
    private $removableRedirectFinder;

    /** @var ValidatorInterface */
    private $validator;

    /** @var array */
    private $validationGroups;

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

        $oldUrl = $this->generateUrl($slugUpdateHandlerCommand->getOldSlug());
        $newUrl = $this->generateUrl($slugUpdateHandlerCommand->getNewSlug());

        $channels = [];

        if ($obj instanceof ChannelAwareInterface && $obj->getChannel() !== null) {
            $channels[] = $obj->getChannel();
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

    // todo missing a locale
    abstract protected function generateUrl(string $slug): string;
}
