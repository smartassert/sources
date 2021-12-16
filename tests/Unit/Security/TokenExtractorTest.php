<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\AuthorizationRequestProperties;
use App\Security\TokenExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class TokenExtractorTest extends TestCase
{
    /**
     * @dataProvider extractDataProvider
     */
    public function testExtract(TokenExtractor $tokenExtractor, Request $request, ?string $expected): void
    {
        self::assertSame($expected, $tokenExtractor->extract($request));
    }

    /**
     * @return array<mixed>
     */
    public function extractDataProvider(): array
    {
        $defaultTokenExtractor = new TokenExtractor(new AuthorizationRequestProperties(
            'Authorization',
            'Bearer '
        ));

        return [
            'authorization header not present' => [
                'tokenExtractor' => $defaultTokenExtractor,
                'request' => new Request(),
                'expected' => null,
            ],
            'authorization header not starts with prefix' => [
                'tokenExtractor' => $defaultTokenExtractor,
                'request' => $this->createRequest([
                    'Authorization' => 'jwt token without prefix'
                ]),
                'expected' => null,
            ],
            'authorization header starts with prefix' => [
                'tokenExtractor' => $defaultTokenExtractor,
                'request' => $this->createRequest([
                    'Authorization' => 'Bearer jwt token'
                ]),
                'expected' => 'jwt token',
            ],
            'authorization header (lowercase) starts with prefix' => [
                'tokenExtractor' => $defaultTokenExtractor,
                'request' => $this->createRequest([
                    'authorization' => 'Bearer jwt token'
                ]),
                'expected' => 'jwt token',
            ],
        ];
    }

    /**
     * @param array<string, string> $headers
     */
    private function createRequest(array $headers): Request
    {
        $request = new Request();

        foreach ($headers as $key => $value) {
            $request->headers->set($key, $value);
        }

        return $request;
    }
}
