<?php

declare(strict_types=1);

namespace App\Tests\Services;

class InvalidFilenameResponseDataFactory
{
    /**
     * @return array<mixed>
     */
    public static function createForMessage(string $message): array
    {
        return [
            'error' => [
                'type' => 'invalid_request',
                'payload' => [
                    'name' => [
                        'value' => '',
                        'message' => $message,
                    ],
                ],
            ],
        ];
    }
}
