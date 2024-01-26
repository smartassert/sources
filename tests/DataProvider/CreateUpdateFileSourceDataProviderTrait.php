<?php

declare(strict_types=1);

namespace App\Tests\DataProvider;

use App\Entity\AbstractSource;
use App\Request\FileSourceRequest;

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
                'requestParameters' => [],
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'parameter' => [
                        'name' => 'label',
                        'value' => '',
                        'requirements' => [
                            'data_type' => 'string',
                            'size' => [
                                'minimum' => 1,
                                'maximum' => 255,
                            ]
                        ],
                    ],
                    'type' => 'empty',
                ],
            ],
            'label length exceeds length limit' => [
                'requestParameters' => [
                    FileSourceRequest::PARAMETER_LABEL => $labelTooLong,
                ],
                'expectedResponseData' => [
                    'class' => 'bad_request',
                    'parameter' => [
                        'name' => 'label',
                        'value' => $labelTooLong,
                        'requirements' => [
                            'data_type' => 'string',
                            'size' => [
                                'minimum' => 1,
                                'maximum' => 255,
                            ]
                        ],
                    ],
                    'type' => 'too_large',
                ],
            ],
        ];
    }
}
