<?php

declare(strict_types=1);

namespace App\RequestField\Field;

use App\RequestField\StringFieldInterface;

readonly class StringField extends Field implements StringFieldInterface
{
    private StringRequirements $requirements;

    /**
     * @param non-empty-string $name
     */
    public function __construct(
        string $name,
        string $value,
        int $minimumLength,
        int $maximumLength,
    ) {
        $this->requirements = new StringRequirements(new Size($minimumLength, $maximumLength));

        parent::__construct($name, $value, $this->requirements);
    }

    public function getRequirements(): StringRequirements
    {
        return $this->requirements;
    }
}
