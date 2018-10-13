<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class NotFoundListener
{
    /**
     * @var RedirectRepositoryInterface
     */
    private $redirectRepository;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function __construct(RedirectRepositoryInterface $redirectRepository, ObjectManager $objectManager)
    {
        $this->redirectRepository = $redirectRepository;
        $this->objectManager = $objectManager;
    }

    /**
     * @param GetResponseForExceptionEvent $event
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }
        $exception = $event->getException();

        if (!$exception instanceof HttpException || Response::HTTP_NOT_FOUND !== (int) $exception->getStatusCode()) {
            return;
        }

        $redirect = $this->redirectRepository->findEnabledBySource($event->getRequest()->getPathInfo(), true);

        if (null === $redirect) {
            return;
        }

        $redirect->onAccess();
        $this->objectManager->flush();
    
        $nextRedirect = $this->searchNextRedirection($redirect);
        while ($nextRedirect instanceof RedirectInterface) {
            $redirect = $nextRedirect;
            $nextRedirect = $this->searchNextRedirection($redirect);
        }
    
        $request = $event->getRequest();
        $baseUrl = $request->getBaseUrl();
        $targetPath = $redirect->isRelative() ? $baseUrl . $redirect->getDestination() : $redirect->getDestination();

        $event->setResponse(new RedirectResponse($targetPath, $redirect->isPermanent() ? Response::HTTP_MOVED_PERMANENTLY : Response::HTTP_FOUND));
    }
    
    /**
     * @param RedirectInterface $redirect
     *
     * @return null|RedirectInterface
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function searchNextRedirection(RedirectInterface $redirect): ?RedirectInterface
    {
        $nextRedirect = $this->redirectRepository->findEnabledBySource($redirect->getDestination(), true);
        
        return $nextRedirect;
    }
}
