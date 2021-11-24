<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusRedirectPlugin\Behat\Page\Admin\Redirect;

use Sylius\Behat\Page\Admin\Crud\IndexPage;

class IndexRedirectPage extends IndexPage
{
    /**
     * @Then the redirect with source :source and destination :destination should appear in the store
     */
    public function theRedirectIsOnThePage($source, $destination): bool
    {
        $rows = $this->getTableAccessor()->getRowsWithFields($this->getElement('table'), [
            'source' => $source,
            'destination' => $destination,
        ]);

        return count($rows) === 1;
    }
}
