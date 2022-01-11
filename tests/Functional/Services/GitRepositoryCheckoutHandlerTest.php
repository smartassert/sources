<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Exception\ProcessExecutorException;
use App\Services\GitRepositoryCheckoutHandler;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Process\Exception\RuntimeException;

class GitRepositoryCheckoutHandlerTest extends WebTestCase
{
    private GitRepositoryCheckoutHandler $checkoutHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $checkoutHandler = self::getContainer()->get(GitRepositoryCheckoutHandler::class);
        \assert($checkoutHandler instanceof GitRepositoryCheckoutHandler);
        $this->checkoutHandler = $checkoutHandler;
    }

    public function testCheckoutRepositoryDirectoryDoesNotExist(): void
    {
        $this->expectExceptionObject(
            new ProcessExecutorException(
                new RuntimeException(sprintf('The provided cwd "%s" does not exist', __DIR__ . '/does-not-exist'))
            )
        );

        $this->checkoutHandler->checkout(__DIR__ . '/does-not-exist');
    }
}
