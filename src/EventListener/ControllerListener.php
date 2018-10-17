<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

final class ControllerListener
{
    /**
     * @var RedirectRepositoryInterface
     */
    private $redirectRepository;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @param RedirectRepositoryInterface $redirectRepository
     * @param ObjectManager               $objectManager
     */
    public function __construct(RedirectRepositoryInterface $redirectRepository, ObjectManager $objectManager)
    {
        $this->redirectRepository = $redirectRepository;
        $this->objectManager = $objectManager;
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event): void
    {
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        $redirect = $this->redirectRepository->findEnabledBySource($pathInfo, false);

        if ($redirect instanceof RedirectInterface) {
            $redirect->onAccess();
            $this->objectManager->flush();

            $lastRedirect = $this->redirectRepository->findLastRedirect($redirect, false);
            $event->setController(function () use ($lastRedirect): RedirectResponse {
                return new RedirectResponse($lastRedirect->getDestination(), $lastRedirect->isPermanent() ? Response::HTTP_MOVED_PERMANENTLY : Response::HTTP_FOUND);
            });
        }
    }
}
