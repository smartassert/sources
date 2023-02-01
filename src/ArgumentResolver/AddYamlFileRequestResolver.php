<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Request\AddYamlFileRequest;
use SmartAssert\YamlFile\Filename;
use SmartAssert\YamlFile\YamlFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class AddYamlFileRequestResolver implements ArgumentValueResolverInterface
{
    public const KEY_ATTRIBUTE_FILENAME = 'filename';

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return AddYamlFileRequest::class === $argument->getType();
    }

    /**
     * @return AddYamlFileRequest[]
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!$this->supports($request, $argument)) {
            return [];
        }

        $request = new AddYamlFileRequest(
            new YamlFile(
                $this->createFilenameFromRequest($request),
                trim($request->getContent())
            )
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
