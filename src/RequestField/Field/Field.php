<?php

declare(strict_types=1);

namespace App\RequestField\Field;

use App\RequestField\FieldInterface;
use App\RequestField\RequirementsInterface;
use App\RequestField\SerializableFieldInterface;
use App\RequestField\SizeInterface;

readonly class Field implements FieldInterface, SerializableFieldInterface
{
    /**
     * @param non-empty-string     $name
     * @param array<scalar>|scalar $value
     */
    public function __construct(
        private string $name,
        private array|bool|float|int|string $value,
        private ?RequirementsInterface $requirements = null,
        private ?int $errorPosition = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): array|bool|float|int|string
    {
        return $this->value;
    }

    public function getRequirements(): ?RequirementsInterface
    {
        return $this->requirements;
    }

    public function getErrorPosition(): ?int
    {
        return $this->errorPosition;
    }

    public function withErrorPosition(int $position): FieldInterface
    {
        return new Field($this->name, $this->value, $this->requirements, $position);
    }

    public function jsonSerialize(): array
    {
        $data = [
            'name' => $this->getName(),
            'value' => $this->getValue(),
        ];

        if (null !== $this->errorPosition) {
            $data['position'] = $this->errorPosition;
        }

        if ($this->requirements instanceof RequirementsInterface) {
            $requirementsData = [
                'data_type' => $this->requirements->getDataType(),
            ];

            $size = $this->requirements->getSize();
            if ($size instanceof SizeInterface) {
                $requirementsData['size'] = ['minimum' => $size->getMinimum(), 'maximum' => $size->getMaximum()];
            }

            $data['requirements'] = $requirementsData;
        }

        return $data;
    }
}
