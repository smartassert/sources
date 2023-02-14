<?php

declare(strict_types=1);

namespace App\RequestValidator;

use App\Exception\InvalidRequestException;
use App\Request\AddYamlFileRequest;
use App\ResponseBody\InvalidField;
use SmartAssert\YamlFile\Validation\ContentContext;
use SmartAssert\YamlFile\Validation\YamlFileContext;
use SmartAssert\YamlFile\Validator\YamlFileValidator;

class AddYamlFileRequestValidator
{
    public function __construct(
        private readonly YamlFileRequestValidator $yamlFileRequestValidator,
        private readonly YamlFileValidator $yamlFileValidator,
    ) {
    }

    /**
     * @throws InvalidRequestException
     */
    public function validate(AddYamlFileRequest $request): void
    {
        $this->yamlFileRequestValidator->validate($request);

        $validation = $this->yamlFileValidator->validate($request->getYamlFile());

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
