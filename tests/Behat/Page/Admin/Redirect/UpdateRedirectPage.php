<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Behat\Page\Admin\Redirect;

use Behat\Mink\Exception\ElementNotFoundException;
use Sylius\Behat\Page\Admin\Crud\UpdatePage;

class UpdateRedirectPage extends UpdatePage
{
    /**
     * @param string $source
     * @throws ElementNotFoundException
     */
    public function specifySource(string $source): void
    {
        $this->getElement('source')->setValue($source);
    }

    /**
     * @param string $destination
     * @throws ElementNotFoundException
     */
    public function specifyDestination(string $destination): void
    {
        $this->getElement('destination')->setValue($destination);
    }

    /**
     * @return string
     * @throws ElementNotFoundException
     */
    public function getSource(): string
    {
        return $this->getElement('source')->getValue();
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefinedElements(): array
    {
        return array_merge(parent::getDefinedElements(), [
            'source' => '#setono_sylius_redirect_redirect_source',
            'destination' => '#setono_sylius_redirect_redirect_destination',
        ]);
    }
}
