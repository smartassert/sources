<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;
use App\Request\GitSourceRequest;
use App\Tests\DataProvider\CreateUpdateFileSourceDataProviderTrait;
use App\Tests\Services\SourceRequestTypeMatcher;
use App\Tests\Services\StringFactory;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class AbstractCreateFileSourceTest extends AbstractApplicationTest
{
    use CreateUpdateFileSourceDataProviderTrait;

    /**
     * @param array<string, string> $requestParameters
     * @param array<string, string> $expectedResponseData
     */
    #[DataProvider('createUpdateFileSourceInvalidRequestDataProvider')]
    public function testCreateInvalidSourceRequest(array $requestParameters, array $expectedResponseData): void
    {
        $response = $this->applicationClient->makeCreateFileSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $requestParameters
        );

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));

        self::assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResponseData),
            $response->getBody()->getContents(),
        );
    }

    /**
     * @param array<string, string> $requestParameters
     * @param array<mixed>          $expected
     */
    #[DataProvider('createFileSourceSuccessDataProvider')]
    public function testCreateSuccess(array $requestParameters, array $expected): void
    {
        $response = $this->applicationClient->makeCreateFileSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $requestParameters
        );

        $sources = [];
        $sourceRepository = self::getContainer()->get(SourceRepository::class);
        if ($sourceRepository instanceof SourceRepository) {
            $sources = $sourceRepository->findAll();
        }

        self::assertIsArray($sources);
        self::assertCount(1, $sources);

        $source = $sources[0];
        self::assertInstanceOf(SourceInterface::class, $source);

        $expected['id'] = $source->getId();
        $expected['user_id'] = self::$users->get(self::USER_1_EMAIL)['id'];

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('content-type'));
        self::assertJsonStringEqualsJsonString((string) json_encode($expected), $response->getBody()->getContents());
    }

    /**
     * @return array<mixed>
     */
    public static function createFileSourceSuccessDataProvider(): array
    {
        $label = StringFactory::createRandom();

        return [
            'file source' => [
                'requestParameters' => [
                    FileSourceRequest::PARAMETER_LABEL => $label
                ],
                'expected' => [
                    'type' => Type::FILE->value,
                    'label' => $label,
                ],
            ],
        ];
    }

    public function testCreateIsIdempotent(): void
    {
        $requestParameters = [
            FileSourceRequest::PARAMETER_LABEL => StringFactory::createRandom(),
        ];

        $firstResponse = $this->applicationClient->makeCreateFileSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $requestParameters
        );

        self::assertSame(200, $firstResponse->getStatusCode());

        $secondResponse = $this->applicationClient->makeCreateFileSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $requestParameters
        );

        self::assertSame(200, $secondResponse->getStatusCode());
        self::assertSame($firstResponse->getBody()->getContents(), $secondResponse->getBody()->getContents());
    }

    public function testCreateWithLabelOfDeletedSource(): void
    {
        $requestParameters = [
            FileSourceRequest::PARAMETER_LABEL => StringFactory::createRandom(),
        ];

        $firstCreateResponse = $this->applicationClient->makeCreateFileSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $requestParameters
        );

        self::assertSame(200, $firstCreateResponse->getStatusCode());

        $firstCreateResponseData = json_decode($firstCreateResponse->getBody()->getContents(), true);
        \assert(is_array($firstCreateResponseData));
        $sourceId = $firstCreateResponseData['id'] ?? null;
        \assert(is_string($sourceId));

        $deleteResponse = $this->applicationClient->makeDeleteSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $sourceId
        );

        self::assertSame(200, $deleteResponse->getStatusCode());

        $secondCreateResponse = $this->applicationClient->makeCreateFileSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $requestParameters
        );

        self::assertSame(200, $secondCreateResponse->getStatusCode());

        $secondCreateResponseData = json_decode($secondCreateResponse->getBody()->getContents(), true);
        \assert(is_array($secondCreateResponseData));
        self::assertNotSame($sourceId, $secondCreateResponseData['id']);
    }

    /**
     * @param array<string, string> $targetCreateParameters
     * @param array<string, string> $conflictCreateParameters
     */
    #[DataProvider('createSourceWithNonUniqueLabelDataProvider')]
    public function testCreateSourceWithNonUniqueLabel(
        string $label,
        array $targetCreateParameters,
        array $conflictCreateParameters,
    ): void {
        if (SourceRequestTypeMatcher::matchesGitSourceRequest($targetCreateParameters)) {
            $firstRequestResponse = $this->applicationClient->makeCreateGitSourceRequest(
                self::$apiTokens->get(self::USER_1_EMAIL),
                $targetCreateParameters
            );
        } else {
            $firstRequestResponse = $this->applicationClient->makeCreateFileSourceRequest(
                self::$apiTokens->get(self::USER_1_EMAIL),
                $targetCreateParameters
            );
        }

        self::assertSame(200, $firstRequestResponse->getStatusCode());

        $secondRequestResponse = $this->applicationClient->makeCreateFileSourceRequest(
            self::$apiTokens->get(self::USER_1_EMAIL),
            $conflictCreateParameters
        );

        $expectedResponseData = [
            'class' => 'duplicate',
            'parameter' => [
                'name' => 'label',
                'value' => $conflictCreateParameters['label'],
            ],
        ];

        self::assertJsonStringEqualsJsonString(
            (string) json_encode($expectedResponseData),
            $secondRequestResponse->getBody()->getContents(),
        );
    }

    /**
     * @return array<mixed>
     */
    public static function createSourceWithNonUniqueLabelDataProvider(): array
    {
        $label = StringFactory::createRandom();

        return [
            'file source with label of git source' => [
                'label' => $label,
                'targetCreateParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $label,
                    GitSourceRequest::PARAMETER_HOST_URL => StringFactory::createRandom(),
                    GitSourceRequest::PARAMETER_PATH => StringFactory::createRandom(),
                ],
                'conflictCreateParameters' => [
                    GitSourceRequest::PARAMETER_LABEL => $label,
                ],
            ],
        ];
    }
}
