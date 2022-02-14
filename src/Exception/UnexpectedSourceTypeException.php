<?php

declare(strict_types=1);

namespace App\Exception;

use App\Entity\SourceInterface;

class UnexpectedSourceTypeException extends \Exception implements HasHttpErrorCodeInterface
{
    public const MESSAGE = 'Source "%s" is not an instance of "%s"';

    /**
     * @param class-string $expectedInstanceClassName
     */
    public function __construct(
        private SourceInterface $source,
        private string $expectedInstanceClassName
    ) {
        parent::__construct(sprintf(self::MESSAGE, $source->getId(), $expectedInstanceClassName));
    }

    public function getSource(): SourceInterface
    {
        return $this->source;
    }

    /**
     * @return class-string
     */
    public function getExpectedInstanceClassName(): string
    {
        return $this->expectedInstanceClassName;
    }

    public function getErrorCode(): int
    {
        return 404;
    }
}
