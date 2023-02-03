<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\SourceInterface;
use App\Enum\Source\Type;
use App\Repository\SourceRepository;
use App\Request\FileSourceRequest;

abstract class AbstractCreateFileSourceTest extends AbstractApplicationTest
{
    /**
     * @dataProvider createSourceInvalidRequestDataProvider
     *
     * @param array<string, string> $requestParameters
     * @param array<string, string> $expectedResponseData
     */
    public function testCreateInvalidSourceRequest(array $requestParameters, array $expectedResponseData): void
    {
        $response = $this->applicationClient->makeCreateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(),
            $requestParameters
        );

        $this->responseAsserter->assertInvalidRequestJsonResponse($response, $expectedResponseData);
    }

    /**
     * @return array<mixed>
     */
    public function createSourceInvalidRequestDataProvider(): array
    {
        return [
            'file source missing label' => [
                'requestParameters' => [],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'label' => [
                                'value' => '',
                                'message' => 'This value should not be blank.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider createSourceSuccessDataProvider
     *
     * @param array<string, string> $requestParameters
     * @param array<mixed>          $expected
     */
    public function testCreateSuccess(array $requestParameters, array $expected): void
    {
        $response = $this->applicationClient->makeCreateFileSourceRequest(
            self::$authenticationConfiguration->getValidApiToken(),
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
        $expected['user_id'] = self::$authenticationConfiguration->getUser()->id;

        $this->responseAsserter->assertSuccessfulJsonResponse($response, $expected);
    }

    /**
     * @return array<mixed>
     */
    public function createSourceSuccessDataProvider(): array
    {
        $label = 'file source label';

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
}
