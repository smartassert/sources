<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Entity\SourceInterface;
use App\Entity\Suite;
use App\Exception\EntityNotFoundException;
use App\Exception\ErrorResponseException;
use App\Repository\SourceRepository;
use App\Request\SuiteRequest;
use App\RequestField\Field\Factory;
use App\RequestField\Validator\StringFieldValidator;
use App\RequestField\Validator\YamlFilenameCollectionFieldValidator;
use App\Security\EntityAccessChecker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

readonly class SuiteRequestResolver implements ValueResolverInterface
{
    public function __construct(
        private SourceRepository $sourceRepository,
        private EntityAccessChecker $entityAccessChecker,
        private StringFieldValidator $fieldValidator,
        private YamlFilenameCollectionFieldValidator $yamlFilenameCollectionFieldValidator,
        private Factory $fieldFactory,
    ) {
    }

    /**
     * @return SuiteRequest[]
     *
     * @throws AccessDeniedException
     * @throws EntityNotFoundException
     * @throws ErrorResponseException
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
     * @throws ErrorResponseException
     */
    private function getLabel(Request $request): string
    {
        return $this->fieldValidator->validateNonEmptyString($this->fieldFactory->createStringField(
            SuiteRequest::PARAMETER_LABEL,
            trim($request->request->getString(SuiteRequest::PARAMETER_LABEL)),
            1,
            Suite::LABEL_MAX_LENGTH,
        ));
    }

    /**
     * @return non-empty-string[]
     *
     * @throws ErrorResponseException
     */
    private function getTests(Request $request): array
    {
        $tests = $request->request->all(SuiteRequest::PARAMETER_TESTS);
        $filteredTests = [];

        foreach ($tests as $requestTest) {
            if (is_string($requestTest) && '' !== $requestTest) {
                $filteredTests[] = $requestTest;
            }
        }

        $testsField = $this->fieldFactory->createYamlFilenameCollectionField(
            SuiteRequest::PARAMETER_TESTS,
            $filteredTests
        );

        return $this->yamlFilenameCollectionFieldValidator->validate($testsField);
    }
}
