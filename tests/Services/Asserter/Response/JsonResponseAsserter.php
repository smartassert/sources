<?php

declare(strict_types=1);

namespace App\Tests\Services\Asserter\Response;

class JsonResponseAsserter extends ResponseAsserter
{
    /**
     * @param array<mixed> $expectedData
     */
    public function __construct(int $expectedStatusCode, array $expectedData)
    {
        parent::__construct($expectedStatusCode);

        $this->addHeaderAsserter(new HeaderAsserter([
            'content-type' => 'application/json'
        ]));

        $this->addBodyAsserter(new ArrayBodyAsserter($expectedData));
    }
}
