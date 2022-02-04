<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Finder\SplFileInfo;

class FilePathIdentifier implements \Stringable
{
    private const TEMPLATE = 'path: "%s"' . "\n" . 'content_hash: "%s"';

    public function __construct(
        private readonly SplFileInfo $file,
        private readonly string $contentHash
    ) {
    }

    public function __toString(): string
    {
        return sprintf(
            self::TEMPLATE,
            addcslashes($this->file->getRelativePathname(), '"'),
            $this->contentHash
        );
    }
}
