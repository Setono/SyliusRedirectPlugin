<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

final class ControllerListener
{
    /** @var RedirectRepositoryInterface */
    private $redirectRepository;

    /** @var ObjectManager */
    private $objectManager;

    public function __construct(RedirectRepositoryInterface $redirectRepository, ObjectManager $objectManager)
    {
        $this->redirectRepository = $redirectRepository;
        $this->objectManager = $objectManager;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        $redirect = $this->redirectRepository->findEnabledBySource($pathInfo);

        if ($redirect instanceof RedirectInterface) {
            $redirect->onAccess();
            $this->objectManager->flush();

            $lastRedirect = $this->redirectRepository->findLastRedirect($redirect);
            $event->setController(static function () use ($lastRedirect): RedirectResponse {
                return new RedirectResponse(
                    $lastRedirect->getDestination(),
                    $lastRedirect->isPermanent() ? Response::HTTP_MOVED_PERMANENTLY : Response::HTTP_FOUND
                );
            });
        }
    }
}
