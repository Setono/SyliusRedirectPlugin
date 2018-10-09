<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Setono\SyliusRedirectPlugin\HashGenerator\HashGeneratorInterface;
use Setono\SyliusRedirectPlugin\Repository\RedirectRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class NotFoundListener
{
    /**
     * @var HashGeneratorInterface
     */
    private $hashGenerator;

    /**
     * @var RedirectRepository
     */
    private $redirectRepository;

    public function __construct(HashGeneratorInterface $hashGenerator, RedirectRepository $redirectRepository)
    {
        $this->hashGenerator = $hashGenerator;
        $this->redirectRepository = $redirectRepository;
    }

    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }
        $exception = $event->getException();

        if (!$exception instanceof HttpException || 404 !== (int) $exception->getStatusCode()) {
            return;
        }

        $hash = $this->hashGenerator->hash($event->getRequest()->getPathInfo());
        $redirect = $this->redirectRepository->findBySourceHash($hash);

        if (null === $redirect) {
            return;
        }

        $event->setResponse(new RedirectResponse($redirect->getDestination(), $redirect->isPermanent() ? 301 : 302));
    }
}
