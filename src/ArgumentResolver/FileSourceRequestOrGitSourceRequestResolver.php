<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\FileSource;
use App\Entity\GitSource;
use App\Enum\Source\Type;
use App\Exception\InvalidRequestException;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Request\OriginSourceRequest;
use App\RequestFactory\FileSourceRequestFactory;
use App\RequestFactory\GitSourceRequestFactory;
use App\ResponseBody\InvalidField;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class FileSourceRequestOrGitSourceRequestResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly FileSourceRequestFactory $fileSourceRequestFactory,
        private readonly GitSourceRequestFactory $gitSourceRequestFactory,
        private readonly SourceRepository $sourceRepository,
    ) {
    }

    /**
     * @return FileSourceRequest[]|GitSourceRequest[]
     *
     * @throws InvalidRequestException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $argumentType = $argument->getType();
        if (null === $argumentType) {
            return [];
        }

        $argumentTypeIsFileSourceRequest = str_contains($argumentType, FileSourceRequest::class);
        $argumentTypeIsGitSourceRequest = str_contains($argumentType, GitSourceRequest::class);

        if (!($argumentTypeIsFileSourceRequest && $argumentTypeIsGitSourceRequest)) {
            return [];
        }

        $sourceType = $this->getRequestSourceType($request);
        if (Type::FILE->value === $sourceType) {
            return [$this->fileSourceRequestFactory->create($request)];
        }

        if (Type::GIT->value === $sourceType) {
            return [$this->gitSourceRequestFactory->create($request)];
        }

        throw new InvalidRequestException(
            $request,
            new InvalidField(
                'type',
                (string) $sourceType,
                'Source type must be one of: file, git.'
            )
        );
    }

    private function getRequestSourceType(Request $request): string
    {
        $sourceId = $request->attributes->get('sourceId');
        if (is_string($sourceId)) {
            $source = $this->sourceRepository->find($sourceId);

            if ($source instanceof FileSource) {
                return Type::FILE->value;
            }

            if ($source instanceof GitSource) {
                return Type::GIT->value;
            }
        }

        return (string) $request->request->get(OriginSourceRequest::PARAMETER_TYPE);
    }
}
