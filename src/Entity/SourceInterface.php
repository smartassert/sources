<?php

declare(strict_types=1);

namespace App\Entity;

interface SourceInterface
{
    public const TYPE_GIT = 'git';
    public const TYPE_FILE = 'file';
    public const TYPE_RUN = 'run';

    public function getId(): string;

    public function getUserId(): string;

    /**
     * @return self::TYPE_*
     */
    public function getType(): string;
}
