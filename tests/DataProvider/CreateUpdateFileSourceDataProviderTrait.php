<?php

declare(strict_types=1);

namespace App\Tests\DataProvider;

use App\Entity\AbstractSource;
use App\Enum\Source\Type;
use App\Request\FileSourceRequest;
use App\Request\OriginSourceRequest;

trait CreateUpdateFileSourceDataProviderTrait
{
    /**
     * @return array<mixed>
     */
    public static function createUpdateFileSourceInvalidRequestDataProvider(): array
    {
        $labelTooLong = str_repeat('.', AbstractSource::LABEL_MAX_LENGTH + 1);

        return [
            'missing label' => [
                'requestParameters' => [
                    OriginSourceRequest::PARAMETER_TYPE => Type::FILE->value,
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'name' => 'label',
                            'value' => '',
                            'message' => 'This value should be between 1 and 255 characters long.',
                        ],
                    ],
                ],
            ],
            'label length exceeds length limit' => [
                'requestParameters' => [
                    OriginSourceRequest::PARAMETER_TYPE => Type::FILE->value,
                    FileSourceRequest::PARAMETER_LABEL => $labelTooLong,
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'name' => 'label',
                            'value' => $labelTooLong,
                            'message' => 'This value should be between 1 and 255 characters long.',
                        ],
                    ],
                ],
            ],
        ];
    }
}
