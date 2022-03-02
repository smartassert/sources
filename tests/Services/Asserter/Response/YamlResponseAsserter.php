<?php

declare(strict_types=1);

namespace App\Tests\Services\Asserter\Response;

class YamlResponseAsserter extends ResponseAsserter
{
    public const IGNORE_VALUE = null;

    public function __construct(int $expectedStatusCode, string $expectedBody)
    {
        parent::__construct($expectedStatusCode);

        $this->addHeaderAsserter(new HeaderAsserter([
            'content-type' => 'text/x-yaml; charset=utf-8'
        ]));

        $this->addBodyAsserter(new BodyContentAsserter($expectedBody));
    }
}
