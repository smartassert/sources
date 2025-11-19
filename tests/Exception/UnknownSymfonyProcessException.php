<?php

declare(strict_types=1);

namespace App\Tests\Exception;

use Symfony\Component\Process\Exception\ExceptionInterface;

class UnknownSymfonyProcessException extends \Exception implements ExceptionInterface {}
