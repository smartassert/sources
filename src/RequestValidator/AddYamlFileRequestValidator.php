<?php

declare(strict_types=1);

namespace App\RequestValidator;

use App\Exception\InvalidRequestException;
use App\Request\AddYamlFileRequest;
use App\ResponseBody\InvalidField;
use SmartAssert\YamlFile\Validation\ContentContext;
use SmartAssert\YamlFile\Validation\YamlFileContext;
use SmartAssert\YamlFile\Validator\YamlFilenameValidator;
use SmartAssert\YamlFile\Validator\YamlFileValidator;

readonly class AddYamlFileRequestValidator
{
    public const MESSAGE_NAME_INVALID =
        'File name must be non-empty, '
        . 'have a .yml or .yaml extension, '
        . 'and contain no backslash or null byte characters.';

    public function __construct(
        private YamlFileValidator $yamlFileValidator,
        private YamlFilenameValidator $yamlFilenameValidator,
    ) {
    }

    /**
     * @throws InvalidRequestException
     */
    public function validate(AddYamlFileRequest $request): void
    {
        $validation = $this->yamlFilenameValidator->validate($request->filename);
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

        $validation = $this->yamlFileValidator->validate($request->file);

        if (!$validation->isValid() && YamlFileContext::CONTENT === $validation->getContext()) {
            $previousContext = $validation->getPrevious()?->getContext();

            if (ContentContext::NOT_EMPTY === $previousContext) {
                throw new InvalidRequestException(
                    $request,
                    new InvalidField(
                        'content',
                        '',
                        'File content must not be empty.',
                    ),
                );
            }

            if (ContentContext::IS_YAML === $previousContext) {
                throw new InvalidRequestException(
                    $request,
                    new InvalidField(
                        'content',
                        '',
                        'Content must be valid YAML: ' . $validation->getPrevious()?->getErrorMessage(),
                    ),
                );
            }
        }
    }
}
