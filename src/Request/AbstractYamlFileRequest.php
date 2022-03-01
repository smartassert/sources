<?php

declare(strict_types=1);

namespace App\Request;

use App\Model\Filename;
use App\Model\YamlFilename;
use App\Validator\YamlFilenameConstraint;
use Symfony\Component\HttpFoundation\Request;
use webignition\EncapsulatingRequestResolverBundle\Model\EncapsulatingRequestInterface;

abstract class AbstractYamlFileRequest implements EncapsulatingRequestInterface
{
    public const KEY_ATTRIBUTE_FILENAME = 'filename';

    public function __construct(
        #[YamlFilenameConstraint]
        private YamlFilename $filename,
    ) {
    }

    public function getFilename(): Filename
    {
        return $this->filename;
    }

    protected static function createFilenameFromRequest(Request $request): YamlFilename
    {
        $filename = $request->attributes->get(self::KEY_ATTRIBUTE_FILENAME);
        $filename = is_scalar($filename) ? (string) $filename : '';

        return new YamlFilename(trim($filename));
    }
}
