<?php

declare(strict_types=1);

namespace App\Request;

use App\Model\Filename;
use App\Validator\FilenameConstraint;
use Symfony\Component\HttpFoundation\Request;
use webignition\EncapsulatingRequestResolverBundle\Model\EncapsulatingRequestInterface;

class RemoveFileRequest implements EncapsulatingRequestInterface
{
    public const KEY_ATTRIBUTE_FILENAME = 'filename';

    public function __construct(
        #[FilenameConstraint]
        private Filename $filename,
    ) {
    }

    public static function create(Request $request): self
    {
        $filename = $request->attributes->get(self::KEY_ATTRIBUTE_FILENAME);
        $filename = is_scalar($filename) ? (string) $filename : '';

        return new self(new Filename(trim($filename)));
    }

    public function getFilename(): Filename
    {
        return $this->filename;
    }
}
