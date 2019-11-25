<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusRedirectPlugin\Decider\AutomaticRedirectCreationDeciderInterface;
use Setono\SyliusRedirectPlugin\Factory\RedirectFactoryInterface;
use Setono\SyliusRedirectPlugin\Finder\RemovableRedirectFinderInterface;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Resource\Model\SlugAwareInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webmozart\Assert\Assert;

abstract class AbstractTranslationUpdateListener
{
    /** @var ValidatorInterface */
    protected $validator;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var RemovableRedirectFinderInterface */
    protected $removableRedirectFinder;

    /** @var RedirectFactoryInterface */
    protected $redirectFactory;

    /** @var AutomaticRedirectCreationDeciderInterface */
    protected $automaticRedirectCreationDecider;

    /** @var array */
    protected $validationGroups;

    /** @var string */
    protected $class;

    /** @var ObjectManager|null */
    private $manager;

    public function __construct(
        ValidatorInterface $validator,
        ManagerRegistry $managerRegistry,
        RemovableRedirectFinderInterface $removableRedirectFinder,
        RedirectFactoryInterface $redirectFactory,
        AutomaticRedirectCreationDeciderInterface $automaticRedirectCreationDecider,
        array $validationGroups,
        string $class
    ) {
        $this->validator = $validator;
        $this->managerRegistry = $managerRegistry;
        $this->removableRedirectFinder = $removableRedirectFinder;
        $this->redirectFactory = $redirectFactory;
        $this->automaticRedirectCreationDecider = $automaticRedirectCreationDecider;
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

        if (!$this->automaticRedirectCreationDecider->isAutomaticRedirectCreationAsked($slugAware)) {
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
}
