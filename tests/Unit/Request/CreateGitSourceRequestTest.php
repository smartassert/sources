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
        $hostUrl = 'https://example.com/repository.git';
        $path = '/path/to/source';
        $accessToken = 'e2d940b51d53c18a73dfe939b95002f9';

        return [
            'empty' => [
                'request' => new Request(),
                'expected' => new CreateGitSourceRequest('', '', null)
            ],
            'user id, host url, path present, access token missing' => [
                'request' => new Request(
                    request: [
                        CreateGitSourceRequest::KEY_POST_HOST_URL => $hostUrl,
                        CreateGitSourceRequest::KEY_POST_PATH => $path,
                    ]
                ),
                'expected' => new CreateGitSourceRequest($hostUrl, $path, null)
            ],
            'user id, host url, path  access token present' => [
                'request' => new Request(
                    request: [
                        CreateGitSourceRequest::KEY_POST_HOST_URL => $hostUrl,
                        CreateGitSourceRequest::KEY_POST_PATH => $path,
                        CreateGitSourceRequest::KEY_POST_ACCESS_TOKEN => $accessToken,
                    ]
                ),
                'expected' => new CreateGitSourceRequest($hostUrl, $path, $accessToken)
            ],
        ];
    }
}
