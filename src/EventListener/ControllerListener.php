<?php

declare(strict_types = 1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

final class ControllerListener
{
    /** @var RedirectRepositoryInterface */
    private $redirectionRepository;
    /** @var ObjectManager */
    private $objectManager;
    
    /**
     * ControllerListener constructor.
     *
     * @param RedirectRepositoryInterface $redirectionRepository
     * @param ObjectManager               $objectManager
     */
    public function __construct(RedirectRepositoryInterface $redirectionRepository, ObjectManager $objectManager)
    {
        $this->redirectionRepository = $redirectionRepository;
        $this->objectManager = $objectManager;
    }
    
    /**
     * @param FilterControllerEvent $event
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function onKernelController(FilterControllerEvent $event): void
    {
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();
        $baseUrl = $request->getBaseUrl();
        
        $redirect = $this->redirectionRepository->findEnabledBySource($pathInfo, false);
        
        if ($redirect instanceof RedirectInterface) {
            $redirect->onAccess();
            $this->objectManager->flush();
            
            $nextRedirect = $this->searchNextRedirection($redirect);
            while ($nextRedirect instanceof RedirectInterface) {
                $redirect = $nextRedirect;
                $nextRedirect = $this->searchNextRedirection($redirect);
            }
            $event->setController(function() use ($redirect, $baseUrl) {
                $targetPath = $redirect->isRelative() ? $baseUrl . $redirect->getDestination() : $redirect->getDestination();
                return new RedirectResponse($targetPath, $redirect->isPermanent() ? Response::HTTP_MOVED_PERMANENTLY : Response::HTTP_FOUND);
            });
        }
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
        $nextRedirect = $this->redirectionRepository->findEnabledBySource($redirect->getDestination(), false);
        
        return $nextRedirect;
    }
}