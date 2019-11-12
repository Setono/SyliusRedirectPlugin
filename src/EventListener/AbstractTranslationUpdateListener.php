<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusRedirectPlugin\Finder\RemovableRedirectFinderInterface;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Resource\Model\SlugAwareInterface;
use Sylius\Component\Resource\Model\TranslationInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webmozart\Assert\Assert;

abstract class AbstractTranslationUpdateListener
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var ValidatorInterface */
    protected $validator;

    /** @var EntityManagerInterface */
    protected $objectManager;

    /** @var RemovableRedirectFinderInterface */
    protected $removableRedirectFinder;

    /** @var array */
    protected $validationGroups;

    /** @var string */
    protected $class;

    public function __construct(RequestStack $requestStack,
                                ValidatorInterface $validator,
                                EntityManagerInterface $objectManager,
                                RemovableRedirectFinderInterface $removableRedirectFinder,
                                array $validationGroups,
                                string $class
    ) {
        $this->requestStack = $requestStack;
        $this->validator = $validator;
        $this->objectManager = $objectManager;
        $this->removableRedirectFinder = $removableRedirectFinder;
        $this->validationGroups = $validationGroups;
        $this->class = $class;
    }

    abstract protected function getPostName(): string;

    abstract protected function createRedirect(SlugAwareInterface $slugAware,
                                     string $source,
                                     string $destination,
                                     bool $permanent = true,
                                     bool $only404 = true
    ): RedirectInterface;

    protected function handleAutomaticRedirectCreation(SlugAwareInterface $slugAware,
                                                       array $previous,
                                                       ResourceControllerEvent $event
    ): void {
        if (!isset($previous['slug'])) {
            return;
        }

        if (!$this->isAutomaticRedirectCreationAsked($slugAware)) {
            return;
        }

        $oldSlug = $previous['slug'];
        $newSlug = $slugAware->getSlug();
        $redirect = $this->createRedirect($slugAware, $oldSlug, $newSlug, true, false);

        $removableRedirects = $this->removableRedirectFinder->findNextRedirect($redirect);
        foreach ($removableRedirects as $removableRedirect) {
            $this->objectManager->remove($removableRedirect);
        }

        $violations = $this->validator->validate($redirect, null, $this->validationGroups);
        if ($violations->count() > 0) {
            /** @var ConstraintViolationInterface $violation */
            foreach ($violations as $violation) {
                $event->stop(
                    $violation->getMessageTemplate(),
                    ResourceControllerEvent::TYPE_ERROR,
                    $violation->getParameters()
                );

                return;
            }
        }

        $this->objectManager->persist($redirect);
    }

    protected function getRequest(): Request
    {
        /** @var Request|null $request */
        $request = $this->requestStack->getCurrentRequest();
        Assert::isInstanceOf($request, Request::class);

        return $request;
    }

    /**
     * Returns true if the automatic creation of a redirect is asked in the request, false otherwise
     */
    protected function isAutomaticRedirectCreationAsked(SlugAwareInterface $slugAware): bool
    {
        /** @var array $postParams */
        $postParams = $this->getRequest()->request->get($this->getPostName(), []);
        if (0 === count($postParams)) {
            return false;
        }

        if ($slugAware instanceof TranslationInterface) {
            $localeCode = $slugAware->getLocale();

            if (!isset($postParams['translations']) || 0 === count($postParams['translations'][$localeCode])) {
                return isset($postParams['addAutomaticRedirect']) && false !== $postParams['addAutomaticRedirect'];
            }

            $translationPostParams = $postParams['translations'][$localeCode];
            if (isset($translationPostParams['addAutomaticRedirect']) && false !== $translationPostParams['addAutomaticRedirect']) {
                return true;
            }
        }

        return isset($postParams['addAutomaticRedirect']) && false !== $postParams['addAutomaticRedirect'];
    }
}
