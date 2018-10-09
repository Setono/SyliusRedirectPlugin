<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\HashGenerator;

class Sha1HashGenerator implements HashGeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function hash(string $value): string
    {
        return sha1($value);
    }
}
