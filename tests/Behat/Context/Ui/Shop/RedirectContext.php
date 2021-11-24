<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Behat\Context\Ui\Shop;

use Behat\Behat\Context\Context;
use Tests\Setono\SyliusRedirectPlugin\Behat\Page\Shop\Page;

final class RedirectContext implements Context
{
    /** @var Page */
    private $oldPathPage;

    public function __construct(Page $oldPathPage)
    {
        $this->oldPathPage = $oldPathPage;
    }

    /**
     * @When I try to access :path
     */
    public function iTryToAccess(string $path)
    {
        $this->oldPathPage->openOldPath($path);
    }

    /**
     * @Then I should be redirected :path
     * @Then I should still be on :path
     */
    public function iShouldBeRedirectedToTheNewPath(string $path): void
    {
        $this->oldPathPage->isOnPath($path);
    }
}
