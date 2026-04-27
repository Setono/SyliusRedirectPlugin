<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Controller\Admin;

use Setono\SyliusRedirectPlugin\Repository\RedirectRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class CheckSourceAction
{
    public function __construct(
        private RedirectRepositoryInterface $redirectRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $source = trim((string) $request->query->get('source', ''));
        if ('' === $source) {
            return new JsonResponse(['exists' => false]);
        }

        $excludeIdRaw = $request->query->get('excludeId');
        $excludeId = (null !== $excludeIdRaw && '' !== $excludeIdRaw) ? (int) $excludeIdRaw : null;

        $redirect = $this->redirectRepository->findOneBySource($source);
        if (null === $redirect || (null !== $excludeId && $redirect->getId() === $excludeId)) {
            return new JsonResponse(['exists' => false]);
        }

        return new JsonResponse([
            'exists' => true,
            'id' => $redirect->getId(),
            'source' => $redirect->getSource(),
            'destination' => $redirect->getDestination(),
            'editUrl' => $this->urlGenerator->generate(
                'setono_sylius_redirect_admin_redirect_update',
                ['id' => $redirect->getId()],
            ),
        ]);
    }
}
