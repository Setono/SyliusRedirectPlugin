<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Behat\Context\Ui\Shop;

use Behat\Behat\Context\Context;
use Tests\Setono\SyliusRedirectPlugin\Behat\Page\Shop\OldPathPage;

final class RedirectContext implements Context
{
    /**
     * @var OldPathPage
     */
    private $oldPathPage;

    public function __construct(OldPathPage $oldPathPage)
    {
        $this->oldPathPage = $oldPathPage;
    }

    /**
     * @When I try to access this old path
     */
    public function iTryToAccess()
    {
        $this->oldPathPage->openOldPath();
    }

    /**
     * @Then I should be redirected to the new path
     */
    public function iShouldBeRedirectedToTheNewPath(): void
    {
        $this->oldPathPage->isOnNewPath();
    }
}
