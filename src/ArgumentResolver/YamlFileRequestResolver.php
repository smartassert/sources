<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Request\YamlFileRequest;
use SmartAssert\YamlFile\Filename;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class YamlFileRequestResolver implements ArgumentValueResolverInterface
{
    public const KEY_ATTRIBUTE_FILENAME = 'filename';

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return YamlFileRequest::class === $argument->getType();
    }

    /**
     * @return YamlFileRequest[]
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!$this->supports($request, $argument)) {
            return [];
        }

        $request = new YamlFileRequest(
            $this->createFilenameFromRequest($request)
        );

        return [$request];
    }

    private function createFilenameFromRequest(Request $request): Filename
    {
        $filename = $request->attributes->get(self::KEY_ATTRIBUTE_FILENAME);
        $filename = is_scalar($filename) ? (string) $filename : '';

        return Filename::parse($filename);
    }
}
