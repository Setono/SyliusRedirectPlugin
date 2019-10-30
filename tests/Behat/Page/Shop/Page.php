<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Behat\Page\Shop;

use FriendsOfBehat\PageObjectExtension\Page\Page as BasePage;
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
        $currentPath = parse_url($this->getSession()->getCurrentUrl(), PHP_URL_PATH);

        Assert::same($currentPath, $path);
    }

    protected function getUrl(array $urlParameters = []): string
    {
        Assert::notNull($this->url);

        return $this->url;
    }
}
