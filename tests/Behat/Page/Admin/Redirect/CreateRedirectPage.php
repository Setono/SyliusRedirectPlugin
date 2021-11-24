<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Behat\Page\Admin\Redirect;

use Behat\Mink\Exception\ElementNotFoundException;
use Sylius\Behat\Page\Admin\Crud\CreatePage as BaseCreatePage;

class CreateRedirectPage extends BaseCreatePage
{
    /**
     * @throws ElementNotFoundException
     */
    public function specifySource(string $source): void
    {
        $this->getElement('source')->setValue($source);
    }

    /**
     * @throws ElementNotFoundException
     */
    public function specifyDestination(string $destination): void
    {
        $this->getElement('destination')->setValue($destination);
    }

    /**
     * @inheritdoc
     */
    protected function getDefinedElements(): array
    {
        return array_merge(parent::getDefinedElements(), [
            'source' => '#setono_sylius_redirect_redirect_source',
            'destination' => '#setono_sylius_redirect_redirect_destination',
        ]);
    }
}
