<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Setono\SyliusRedirectPlugin\Model\RedirectInterface;
use Tests\Setono\SyliusRedirectPlugin\Behat\Page\Admin\Redirect\CreateRedirectPage;
use Tests\Setono\SyliusRedirectPlugin\Behat\Page\Admin\Redirect\IndexRedirectPage;
use Tests\Setono\SyliusRedirectPlugin\Behat\Page\Admin\Redirect\UpdateRedirectPage;
use Webmozart\Assert\Assert;

final class ManagingRedirectsContext implements Context
{
    private IndexRedirectPage $indexRedirectPage;

    private CreateRedirectPage $createRedirectPage;

    private UpdateRedirectPage $updateRedirectPage;

    public function __construct(IndexRedirectPage $indexRedirectPage, CreateRedirectPage $createRedirectPage, UpdateRedirectPage $updateRedirectPage)
    {
        $this->indexRedirectPage = $indexRedirectPage;
        $this->createRedirectPage = $createRedirectPage;
        $this->updateRedirectPage = $updateRedirectPage;
    }

    /**
     * @Given I want to create a new redirect
     */
    public function iWantToCreateANewRedirect(): void
    {
        $this->createRedirectPage->open();
    }

    /**
     * @When I set its source to :source
     */
    public function iSetItsSourceTo($source): void
    {
        $this->createRedirectPage->specifySource($source);
    }

    /**
     * @When I set its destination to :destination
     */
    public function iSetItsDestinationTo($destination): void
    {
        $this->createRedirectPage->specifyDestination($destination);
    }

    /**
     * @When I add it
     */
    public function iAddIt(): void
    {
        $this->createRedirectPage->create();
    }

    /**
     * @Then the redirect with source :source and destination :destination should appear in the store
     */
    public function theRedirectShouldAppearInTheStore($source, $destination): void
    {
        $this->indexRedirectPage->open();

        Assert::true(
            $this->indexRedirectPage->theRedirectIsOnThePage($source, $destination),
            sprintf('Redirect with source %s and destination %s should exist but it does not', $source, $destination)
        );
    }

    /**
     * @Given I want to modify the redirect with source :redirect
     */
    public function iWantToModifyTheRedirectWithSource(RedirectInterface $redirect): void
    {
        $this->updateRedirectPage->open([
            'id' => $redirect->getId(),
        ]);
    }

    /**
     * @When I update the source to :source
     */
    public function iUpdateTheSourceTo($source): void
    {
        $this->updateRedirectPage->specifySource($source);
    }

    /**
     * @When I save my changes
     */
    public function iSaveMyChanges(): void
    {
        $this->updateRedirectPage->saveChanges();
    }

    /**
     * @Then this redirects source should be :source
     */
    public function thisRedirectsSourceShouldBe($source): void
    {
        Assert::eq($source, $this->updateRedirectPage->getSource());
    }
}
