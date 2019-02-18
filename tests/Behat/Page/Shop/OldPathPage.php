<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Behat\Page\Shop;

use FriendsOfBehat\PageObjectExtension\Page\Page;
use FriendsOfBehat\PageObjectExtension\Page\UnexpectedPageException;

class OldPathPage extends Page
{
    public function openOldPath(): void
    {
        $this->tryToOpen();
    }

    /**
     * @throws UnexpectedPageException
     */
    public function isOnNewPath(): void
    {
        $currentPath = parse_url($this->getSession()->getCurrentUrl(), PHP_URL_PATH);

        if($currentPath === '/new-path') {
            return;
        }

        throw new UnexpectedPageException(sprintf('Expected to be on "%s" but found "%s" instead', '/new-path', $this->getSession()->getCurrentUrl()));
    }

    protected function getUrl(array $urlParameters = []): string
    {
        return '/old-path';
    }

}
