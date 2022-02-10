<?php

declare(strict_types=1);

namespace App\Request;

use App\Model\Filename;
use App\Validator\FilenameConstraint;
use Symfony\Component\HttpFoundation\Request;
use webignition\EncapsulatingRequestResolverBundle\Model\EncapsulatingRequestInterface;

abstract class AbstractFileActionRequest implements EncapsulatingRequestInterface
{
    public const KEY_ATTRIBUTE_FILENAME = 'filename';

    public function __construct(
        #[FilenameConstraint]
        private Filename $filename,
    ) {
    }

    public function getFilename(): Filename
    {
        return $this->filename;
    }

    protected static function createFilenameFromRequest(Request $request): Filename
    {
        $filename = $request->attributes->get(self::KEY_ATTRIBUTE_FILENAME);
        $filename = is_scalar($filename) ? (string) $filename : '';

        return new Filename(trim($filename));
    }
}
