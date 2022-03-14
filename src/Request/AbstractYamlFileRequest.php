<?php

declare(strict_types=1);

namespace App\Request;

use App\Validator\YamlFilenameConstraint;
use SmartAssert\YamlFile\Model\Filename;
use Symfony\Component\HttpFoundation\Request;
use webignition\EncapsulatingRequestResolverBundle\Model\EncapsulatingRequestInterface;

abstract class AbstractYamlFileRequest implements EncapsulatingRequestInterface
{
    public const KEY_ATTRIBUTE_FILENAME = 'filename';

    public function __construct(
        #[YamlFilenameConstraint]
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

        return Filename::parse($filename);
    }
}
