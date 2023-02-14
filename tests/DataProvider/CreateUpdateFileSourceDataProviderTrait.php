<?php

declare(strict_types=1);

namespace App\Tests\DataProvider;

use App\Entity\AbstractOriginSource;
use App\Request\FileSourceRequest;

trait CreateUpdateFileSourceDataProviderTrait
{
    /**
     * @return array<mixed>
     */
    public function createUpdateFileSourceInvalidRequestDataProvider(): array
    {
        $labelTooLong = str_repeat('.', AbstractOriginSource::LABEL_MAX_LENGTH + 1);

        return [
            'missing label' => [
                'requestParameters' => [],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'label' => [
                                'name' => 'label',
                                'value' => '',
                                'message' => 'This value is too short. It should have 1 character or more.',
                            ],
                        ],
                    ],
                ],
            ],
            'label length exceeds length limit' => [
                'requestParameters' => [
                    FileSourceRequest::PARAMETER_LABEL => $labelTooLong,
                ],
                'expectedResponseData' => [
                    'error' => [
                        'type' => 'invalid_request',
                        'payload' => [
                            'label' => [
                                'name' => 'label',
                                'value' => $labelTooLong,
                                'message' => 'This value is too long. It should have 255 characters or less.',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
