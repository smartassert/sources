<?php

declare(strict_types=1);

namespace App\Tests\Services\Asserter\Response;

class TextPlainResponseAsserter extends ResponseAsserter
{
    public function __construct(int $expectedStatusCode)
    {
        parent::__construct($expectedStatusCode);

        $this->addHeaderAsserter(new HeaderAsserter([
            'content-type' => 'text/plain; charset=UTF-8'
        ]));
    }
}
