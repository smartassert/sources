<?php

declare(strict_types=1);

namespace App\Tests\Unit\Request;

use App\Request\CreateSourceRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class CreateSourceRequestTest extends TestCase
{
    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(Request $request, CreateSourceRequest $expected): void
    {
        self::assertEquals($expected, CreateSourceRequest::create($request));
    }

    /**
     * @return array<mixed>
     */
    public function createDataProvider(): array
    {
        return [
            'empty' => [
                'request' => new Request(),
                'expected' => new CreateSourceRequest('', '', null)
            ],
            'user id, host url, path present, access token missing' => [
                'request' => new Request(
                    request: [
                        CreateSourceRequest::KEY_POST_HOST_URL => 'https://example.com/repository.git',
                        CreateSourceRequest::KEY_POST_PATH => '/path/to/source',
                    ]
                ),
                'expected' => new CreateSourceRequest(
                    'https://example.com/repository.git',
                    '/path/to/source',
                    null
                )
            ],
            'user id, host url, path  access token present' => [
                'request' => new Request(
                    request: [
                        CreateSourceRequest::KEY_POST_HOST_URL => 'https://example.com/repository.git',
                        CreateSourceRequest::KEY_POST_PATH => '/path/to/source',
                        CreateSourceRequest::KEY_POST_ACCESS_TOKEN => 'e2d940b51d53c18a73dfe939b95002f9',
                    ]
                ),
                'expected' => new CreateSourceRequest(
                    'https://example.com/repository.git',
                    '/path/to/source',
                    'e2d940b51d53c18a73dfe939b95002f9'
                )
            ],
        ];
    }
}
