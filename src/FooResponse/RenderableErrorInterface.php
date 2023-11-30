<?php

declare(strict_types=1);

namespace App\FooResponse;

interface RenderableErrorInterface extends BadRequestErrorInterface
{
    public const SHOW_REQUIREMENTS = true;
    public const DO_NOT_SHOW_REQUIREMENTS = false;

    public function renderRequirements(): bool;
}
