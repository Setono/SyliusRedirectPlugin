<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Behat\Page\Shop;

use FriendsOfBehat\PageObjectExtension\Page\Page as BasePage;
use League\Uri\Uri;
use Webmozart\Assert\Assert;

class Page extends BasePage
{
    /** @var string */
    protected $url;

    public function openOldPath(string $path): void
    {
        $this->url = $path;

        $this->tryToOpen();
    }

    public function isOnPath(string $path): void
    {
        $currentPath = (Uri::createFromString($this->getSession()->getCurrentUrl()))
            ->withScheme(null)
            ->withHost(null);

        Assert::same($currentPath->__toString(), $path);
    }

    protected function getUrl(array $urlParameters = []): string
    {
        Assert::notNull($this->url);

        return $this->url;
    }
}
