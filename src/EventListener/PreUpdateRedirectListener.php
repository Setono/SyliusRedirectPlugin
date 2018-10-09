<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\EventListener;

use Setono\SyliusRedirectPlugin\HashGenerator\HashGeneratorInterface;

class PreUpdateRedirectListener
{
    /**
     * @var HashGeneratorInterface
     */
    private $hashGenerator;

    public function __construct(HashGeneratorInterface $hashGenerator)
    {
        $this->hashGenerator = $hashGenerator;
    }

    public function update(): void
    {

    }
}
