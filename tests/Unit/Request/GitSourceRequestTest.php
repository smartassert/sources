<?php

declare(strict_types=1);

namespace App\Tests\Unit\Request;

use App\Request\GitSourceRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class GitSourceRequestTest extends TestCase
{
    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(Request $request, GitSourceRequest $expected): void
    {
        self::assertEquals($expected, GitSourceRequest::create($request));
    }

    /**
     * @return array<mixed>
     */
    public function createDataProvider(): array
    {
        $hostUrl = 'https://example.com/repository.git';
        $path = '/path/to/source';
        $credentials = 'e2d940b51d53c18a73dfe939b95002f9';

        return [
            'empty' => [
                'request' => new Request(),
                'expected' => new GitSourceRequest('', '', '')
            ],
            'user id, host url, path present, credentials missing' => [
                'request' => new Request(
                    request: [
                        GitSourceRequest::KEY_POST_HOST_URL => $hostUrl,
                        GitSourceRequest::KEY_POST_PATH => $path,
                    ]
                ),
                'expected' => new GitSourceRequest($hostUrl, $path, '')
            ],
            'user id, host url, path credentials present, set to null' => [
                'request' => new Request(
                    request: [
                        GitSourceRequest::KEY_POST_HOST_URL => $hostUrl,
                        GitSourceRequest::KEY_POST_PATH => $path,
                        GitSourceRequest::KEY_POST_CREDENTIALS => null,
                    ]
                ),
                'expected' => new GitSourceRequest($hostUrl, $path, '')
            ],
            'user id, host url, path credentials present, set to string' => [
                'request' => new Request(
                    request: [
                        GitSourceRequest::KEY_POST_HOST_URL => $hostUrl,
                        GitSourceRequest::KEY_POST_PATH => $path,
                        GitSourceRequest::KEY_POST_CREDENTIALS => $credentials,
                    ]
                ),
                'expected' => new GitSourceRequest($hostUrl, $path, $credentials)
            ],
        ];
    }
}
