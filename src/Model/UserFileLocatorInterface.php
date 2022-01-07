<?php

declare(strict_types=1);

namespace App\Model;

interface UserFileLocatorInterface extends FileLocatorInterface
{
    public function getId(): string;

    public function getUserId(): string;
}
