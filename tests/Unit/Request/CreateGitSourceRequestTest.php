<?php

declare(strict_types=1);

namespace App\Tests\Unit\Request;

use App\Request\CreateGitSourceRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class CreateGitSourceRequestTest extends TestCase
{
    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(Request $request, CreateGitSourceRequest $expected): void
    {
        self::assertEquals($expected, CreateGitSourceRequest::create($request));
    }

    /**
     * @return array<mixed>
     */
    public function createDataProvider(): array
    {
        return [
            'empty' => [
                'request' => new Request(),
                'expected' => new CreateGitSourceRequest('', '', null, null)
            ],
            'user id, host url, path present, access token missing, ref missing' => [
                'request' => new Request(
                    request: [
                        CreateGitSourceRequest::KEY_POST_HOST_URL => 'https://example.com/repository.git',
                        CreateGitSourceRequest::KEY_POST_PATH => '/path/to/source',
                    ]
                ),
                'expected' => new CreateGitSourceRequest(
                    'https://example.com/repository.git',
                    '/path/to/source',
                    null,
                    null
                )
            ],
            'user id, host url, path  access token present, ref missing' => [
                'request' => new Request(
                    request: [
                        CreateGitSourceRequest::KEY_POST_HOST_URL => 'https://example.com/repository.git',
                        CreateGitSourceRequest::KEY_POST_PATH => '/path/to/source',
                        CreateGitSourceRequest::KEY_POST_ACCESS_TOKEN => 'e2d940b51d53c18a73dfe939b95002f9',
                    ]
                ),
                'expected' => new CreateGitSourceRequest(
                    'https://example.com/repository.git',
                    '/path/to/source',
                    'e2d940b51d53c18a73dfe939b95002f9',
                    null
                )
            ],
            'user id, host url, path  access token present, ref present' => [
                'request' => new Request(
                    request: [
                        CreateGitSourceRequest::KEY_POST_HOST_URL => 'https://example.com/repository.git',
                        CreateGitSourceRequest::KEY_POST_PATH => '/path/to/source',
                        CreateGitSourceRequest::KEY_POST_ACCESS_TOKEN => 'e2d940b51d53c18a73dfe939b95002f9',
                        CreateGitSourceRequest::KEY_POST_REF => 'v0.1',
                    ]
                ),
                'expected' => new CreateGitSourceRequest(
                    'https://example.com/repository.git',
                    '/path/to/source',
                    'e2d940b51d53c18a73dfe939b95002f9',
                    'v0.1'
                )
            ],
        ];
    }
}
