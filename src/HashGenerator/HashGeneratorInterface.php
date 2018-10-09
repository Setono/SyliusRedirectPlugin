<?php

declare(strict_types=1);

namespace Setono\SyliusRedirectPlugin\HashGenerator;

interface HashGeneratorInterface
{
    /**
     * Hashes the given string
     *
     * @param string $value
     *
     * @return string
     */
    public function hash(string $value): string;
}
