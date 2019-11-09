<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\Finder;

use Doctrine\Common\Collections\Collection;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;

interface RemovableRedirectFinderInterface
{
    /**
     * @return Collection|RedirectInterface[]
     */
    public function findNextRedirect(RedirectInterface $redirect): Collection;
}
