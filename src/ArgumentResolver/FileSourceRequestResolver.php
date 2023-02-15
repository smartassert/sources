<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\AbstractOriginSource;
use App\Exception\InvalidRequestException;
use App\Request\FileSourceRequest;
use App\ResponseBody\InvalidField;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class FileSourceRequestResolver implements ValueResolverInterface
{
    /**
     * @return FileSourceRequest[]
     *
     * @throws InvalidRequestException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (FileSourceRequest::class !== $argument->getType()) {
            return [];
        }

        $label = trim((string) $request->request->get(FileSourceRequest::PARAMETER_LABEL));
        if ('' === $label || mb_strlen($label) > AbstractOriginSource::LABEL_MAX_LENGTH) {
            $message = sprintf(
                'This value should be between 1 and %d characters long.',
                AbstractOriginSource::LABEL_MAX_LENGTH
            );

            throw new InvalidRequestException($request, new InvalidField('label', $label, $message));
        }

        return [new FileSourceRequest($label)];
    }
}
