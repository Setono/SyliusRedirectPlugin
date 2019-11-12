<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusRedirectPlugin\Factory\RedirectFactoryInterface;
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

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var RemovableRedirectFinderInterface */
    protected $removableRedirectFinder;

    /** @var RedirectFactoryInterface */
    protected $redirectFactory;

    /** @var array */
    protected $validationGroups;

    /** @var string */
    protected $class;

    /** @var ObjectManager|null */
    private $manager;

    /** @var Request|null */
    private $request;

    public function __construct(
        RequestStack $requestStack,
        ValidatorInterface $validator,
        ManagerRegistry $managerRegistry,
        RemovableRedirectFinderInterface $removableRedirectFinder,
        RedirectFactoryInterface $redirectFactory,
        array $validationGroups,
        string $class
    ) {
        $this->requestStack = $requestStack;
        $this->validator = $validator;
        $this->managerRegistry = $managerRegistry;
        $this->removableRedirectFinder = $removableRedirectFinder;
        $this->redirectFactory = $redirectFactory;
        $this->validationGroups = $validationGroups;
        $this->class = $class;
    }

    abstract protected function getPostName(): string;

    abstract protected function createRedirect(
        SlugAwareInterface $slugAware,
        string $source,
        string $destination,
        bool $permanent = true,
        bool $only404 = true
    ): RedirectInterface;

    protected function getPrevious(SlugAwareInterface $slugAware): array
    {
        $uow = $this->getManager()->getUnitOfWork();
        $previous = $uow->getOriginalEntityData($slugAware);

        return $previous;
    }

    protected function handleAutomaticRedirectCreation(
        SlugAwareInterface $slugAware,
        ResourceControllerEvent $event
    ): void {
        $previous = $this->getPrevious($slugAware);

        if (!isset($previous['slug'])) {
            return;
        }

        if (!$this->isAutomaticRedirectCreationAsked($slugAware)) {
            return;
        }

        $oldSlug = $previous['slug'];
        $newSlug = $slugAware->getSlug();
        $redirect = $this->createRedirect($slugAware, $oldSlug, $newSlug, true, false);

        $removableRedirects = $this->removableRedirectFinder->findRedirectsTargetedBy($redirect);
        foreach ($removableRedirects as $removableRedirect) {
            $this->getManager()->remove($removableRedirect);
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

        $this->getManager()->persist($redirect);
    }

    protected function getManager(): EntityManagerInterface
    {
        if (!$this->manager instanceof EntityManagerInterface) {
            /** @var EntityManagerInterface|null $manager */
            $manager = $this->managerRegistry->getManagerForClass($this->class);
            Assert::isInstanceOf($manager, EntityManagerInterface::class);

            $this->manager = $manager;
        }

        return $this->manager;
    }

    protected function getRequest(): Request
    {
        if (!$this->request instanceof Request) {
            /** @var Request|null $request */
            $request = $this->requestStack->getCurrentRequest();
            Assert::isInstanceOf($request, Request::class);

            $this->request = $request;
        }

        return $this->request;
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
                return isset($postParams['addAutomaticRedirect']) && true === $postParams['addAutomaticRedirect'];
            }

            $translationPostParams = $postParams['translations'][$localeCode];
            if (isset($translationPostParams['addAutomaticRedirect']) && true === $translationPostParams['addAutomaticRedirect']) {
                return true;
            }
        }

        return isset($postParams['addAutomaticRedirect']) && true === $postParams['addAutomaticRedirect'];
    }
}
