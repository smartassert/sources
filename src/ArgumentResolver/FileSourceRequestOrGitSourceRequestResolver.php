<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Enum\Source\Type;
use App\Exception\InvalidRequestException;
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

        if (!$argumentTypeIsFileSourceRequest && !$argumentTypeIsGitSourceRequest) {
            return [];
        }

        $sourceType = $request->request->get(OriginSourceRequest::PARAMETER_TYPE);

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
}
