<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Entity\FileSource;
use App\Entity\RunSource;

abstract class AbstractPrepareSourceTest extends AbstractApplicationTest
{
    public function testPrepareRunSource(): void
    {
        $fileSource = new FileSource($this->authenticationConfiguration->authenticatedUserId, 'file source label');
        $source = new RunSource($fileSource);

        $this->store->add($source);

        $response = $this->applicationClient->makePrepareSourceRequest(
            $this->authenticationConfiguration->validToken,
            $source->getId(),
            []
        );

        $this->responseAsserter->assertNotFoundResponse($response);
    }
}
