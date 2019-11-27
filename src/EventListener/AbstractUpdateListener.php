<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusRedirectPlugin\Decider\AutomaticRedirectCreationDeciderInterface;
use Setono\SyliusRedirectPlugin\Exception\SlugUpdateHandlerException;
use Setono\SyliusRedirectPlugin\Exception\SlugUpdateHandlerValidationException;
use Setono\SyliusRedirectPlugin\SlugUpdateHandler\SlugUpdateHandlerCommand;
use Setono\SyliusRedirectPlugin\SlugUpdateHandler\SlugUpdateHandlerInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Resource\Model\SlugAwareInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Webmozart\Assert\Assert;

abstract class AbstractUpdateListener
{
    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var AutomaticRedirectCreationDeciderInterface */
    protected $automaticRedirectCreationDecider;

    /** @var SlugUpdateHandlerInterface */
    private $slugUpdateHandler;

    /** @var EntityManagerInterface[] */
    private $managers = [];

    public function __construct(
        ManagerRegistry $managerRegistry,
        AutomaticRedirectCreationDeciderInterface $automaticRedirectCreationDecider,
        SlugUpdateHandlerInterface $slugUpdateHandler
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->automaticRedirectCreationDecider = $automaticRedirectCreationDecider;
        $this->slugUpdateHandler = $slugUpdateHandler;
    }

    protected function getPrevious(SlugAwareInterface $slugAware): array
    {
        return $this->getManager(get_class($slugAware))
            ->getUnitOfWork()
            ->getOriginalEntityData($slugAware);
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

        try {
            $this->slugUpdateHandler->handle(new SlugUpdateHandlerCommand($slugAware, $oldSlug, $newSlug));
        } catch (SlugUpdateHandlerValidationException $e) {
            /** @var ConstraintViolationInterface $violation */
            $violation = current(iterator_to_array($e->getConstraintViolationList()));

            $event->stop(
                $violation->getMessageTemplate(),
                ResourceControllerEvent::TYPE_ERROR,
                $violation->getParameters()
            );
        } catch (SlugUpdateHandlerException $e) {
            $event->stop($e->getMessage());
        }
    }

    protected function getManager(string $class): EntityManagerInterface
    {
        if (!isset($this->managers[$class])) {
            $this->managers[$class] = $this->managerRegistry->getManagerForClass($class);

            Assert::isInstanceOf($this->managers[$class], EntityManagerInterface::class);
        }

        return $this->managers[$class];
    }
}
