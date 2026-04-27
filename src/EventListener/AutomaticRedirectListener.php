<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusRedirectPlugin\Exception\SlugUpdateHandlerValidationException;
use Setono\SyliusRedirectPlugin\Factory\RedirectFactoryInterface;
use Setono\SyliusRedirectPlugin\Finder\RemovableRedirectFinderInterface;
use Setono\SyliusRedirectPlugin\UrlResolver\AutomaticRedirectUrlResolverInterface;
use Sylius\Component\Resource\Model\SlugAwareInterface;
use Sylius\Component\Resource\Model\TranslatableInterface;
use Sylius\Component\Resource\Model\TranslationInterface;
use Sylius\Resource\Symfony\EventDispatcher\GenericEvent;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AutomaticRedirectListener implements LoggerAwareInterface
{
    use ORMTrait;

    private LoggerInterface $logger;

    /**
     * @param list<string> $validationGroups
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly RedirectFactoryInterface $redirectFactory,
        private readonly AutomaticRedirectUrlResolverInterface $urlResolver,
        private readonly RemovableRedirectFinderInterface $removableRedirectFinder,
        private readonly ValidatorInterface $validator,
        private readonly array $validationGroups,
        private readonly string $slugField = 'slug',
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function onPreUpdate(GenericEvent $event): void
    {
        $subject = $event->getSubject();
        if (!is_object($subject) || !$subject instanceof TranslatableInterface) {
            return;
        }

        foreach ($subject->getTranslations() as $translation) {
            if (!$translation instanceof TranslationInterface || !$translation instanceof SlugAwareInterface) {
                continue;
            }

            $manager = $this->getManager($translation);
            $metadata = $manager->getClassMetadata($translation::class);
            if (!$metadata->hasField($this->slugField)) {
                $this->logger->error(sprintf(
                    'Translation class "%s" has no Doctrine field named "%s"; cannot determine the old slug for an automatic redirect.',
                    $translation::class,
                    $this->slugField,
                ));

                continue;
            }

            $original = $manager->getUnitOfWork()->getOriginalEntityData($translation);
            if ([] === $original) {
                continue;
            }

            $oldSlug = $original[$this->slugField] ?? null;
            if (!is_string($oldSlug) || '' === $oldSlug) {
                continue;
            }

            $newSlug = $translation->getSlug();
            if (!is_string($newSlug) || '' === $newSlug || $oldSlug === $newSlug) {
                continue;
            }

            $locale = $translation->getLocale();
            if (!is_string($locale) || '' === $locale) {
                continue;
            }

            $this->createRedirect($subject, $oldSlug, $newSlug, $locale);
        }
    }

    private function createRedirect(object $subject, string $oldSlug, string $newSlug, string $locale): void
    {
        $oldUrl = $this->urlResolver->resolve($subject, $oldSlug, $locale);
        $newUrl = $this->urlResolver->resolve($subject, $newSlug, $locale);

        $redirect = $this->redirectFactory->createNewWithValues(
            $oldUrl,
            $newUrl,
            true,
            true,
            [],
        );

        $manager = $this->getManager($redirect);

        $removableRedirects = $this->removableRedirectFinder->findRedirectsTargetedBy($redirect);
        foreach ($removableRedirects as $removableRedirect) {
            $manager->remove($removableRedirect);
        }

        $violations = $this->validator->validate($redirect, null, $this->validationGroups);
        if (count($violations) > 0) {
            throw new SlugUpdateHandlerValidationException($violations);
        }

        $manager->persist($redirect);
    }
}
