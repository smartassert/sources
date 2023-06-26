<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\SourceInterface;
use App\Entity\Suite;
use App\Exception\EntityNotFoundException;
use App\Exception\InvalidRequestException;
use App\Repository\SourceRepository;
use App\Request\SuiteRequest;
use App\ResponseBody\InvalidField;
use App\Security\EntityAccessChecker;
use SmartAssert\YamlFile\Filename;
use SmartAssert\YamlFile\Validator\YamlFilenameValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SuiteRequestResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly SourceRepository $sourceRepository,
        private readonly YamlFilenameValidator $yamlFilenameValidator,
        private readonly EntityAccessChecker $entityAccessChecker,
    ) {
    }

    /**
     * @return SuiteRequest[]
     *
     * @throws AccessDeniedException
     * @throws InvalidRequestException
     * @throws EntityNotFoundException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (SuiteRequest::class !== $argument->getType()) {
            return [];
        }

        return [new SuiteRequest($this->getSource($request), $this->getLabel($request), $this->getTests($request))];
    }

    /**
     * @throws AccessDeniedException
     * @throws EntityNotFoundException
     */
    private function getSource(Request $request): SourceInterface
    {
        $sourceId = $request->request->get(SuiteRequest::PARAMETER_SOURCE_ID);
        $sourceId = is_string($sourceId) ? trim($sourceId) : '';

        $source = $this->sourceRepository->find($sourceId);

        if (null === $source) {
            throw new EntityNotFoundException($sourceId, 'Source');
        }

        $this->entityAccessChecker->denyAccessUnlessGranted($source);

        return $source;
    }

    /**
     * @return non-empty-string
     *
     * @throws InvalidRequestException
     */
    private function getLabel(Request $request): string
    {
        $label = trim((string) $request->request->get(SuiteRequest::PARAMETER_LABEL));
        if ('' === $label || mb_strlen($label) > Suite::LABEL_MAX_LENGTH) {
            $message = sprintf(
                'This value should be between 1 and %d characters long.',
                Suite::LABEL_MAX_LENGTH
            );

            throw new InvalidRequestException($request, new InvalidField('label', $label, $message));
        }

        return $label;
    }

    /**
     * @return non-empty-string[]
     *
     * @throws InvalidRequestException
     */
    private function getTests(Request $request): array
    {
        $requestTests = $request->request->all(SuiteRequest::PARAMETER_TESTS);
        $tests = [];

        foreach ($requestTests as $requestTest) {
            if (is_string($requestTest)) {
                $requestTest = trim($requestTest);

                $validation = $this->yamlFilenameValidator->validate(Filename::parse($requestTest));
                if ($validation->isValid() && '' !== $requestTest) {
                    $tests[] = $requestTest;
                } else {
                    throw new InvalidRequestException(
                        $request,
                        new InvalidField(
                            'tests',
                            implode(', ', $requestTests),
                            'Tests must be valid yaml file paths.'
                        )
                    );
                }
            }
        }

        return $tests;
    }
}
