<?php

declare(strict_types=1);

namespace App\Entity;

interface SourceInterface
{
    public function getId(): string;

    public function getUserId(): string;
}
