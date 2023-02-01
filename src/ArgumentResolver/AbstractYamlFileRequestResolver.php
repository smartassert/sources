<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use SmartAssert\YamlFile\Filename;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;

abstract class AbstractYamlFileRequestResolver implements ArgumentValueResolverInterface
{
    public const KEY_ATTRIBUTE_FILENAME = 'filename';

    protected function createFilenameFromRequest(Request $request): Filename
    {
        $filename = $request->attributes->get(self::KEY_ATTRIBUTE_FILENAME);
        $filename = is_scalar($filename) ? (string) $filename : '';

        return Filename::parse($filename);
    }
}
