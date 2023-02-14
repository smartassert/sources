<?php

declare(strict_types=1);

namespace App\RequestValidator;

use App\Exception\InvalidRequestException;
use App\Request\YamlFileRequest;
use App\ResponseBody\InvalidField;
use SmartAssert\YamlFile\Validator\YamlFilenameValidator;

class YamlFileRequestValidator
{
    public const MESSAGE_NAME_INVALID =
        'File name must be non-empty, '
        . 'have a .yml or .yaml extension, '
        . 'and contain no backslash or null byte characters.';

    public function __construct(
        private readonly YamlFilenameValidator $yamlFilenameValidator,
    ) {
    }

    /**
     * @throws InvalidRequestException
     */
    public function validate(YamlFileRequest $request): void
    {
        $validation = $this->yamlFilenameValidator->validate($request->getFilename());

        if (!$validation->isValid()) {
            throw new InvalidRequestException(
                $request,
                new InvalidField(
                    'name',
                    '',
                    self::MESSAGE_NAME_INVALID
                ),
            );
        }
    }
}
