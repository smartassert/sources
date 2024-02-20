<?php

declare(strict_types=1);

namespace App\EventListener;

use App\RequestParameter\Factory;
use App\RequestParameter\Validator\YamlFilenameParameterValidator;
use App\RequestParameter\Validator\YamlParameterValidator;
use SmartAssert\ServiceRequest\Exception\ErrorResponseException;
use SmartAssert\ServiceRequest\Parameter\Parameter;
use SmartAssert\ServiceRequest\Parameter\Requirements;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

readonly class StoreYamlFileRequestValidator implements EventSubscriberInterface
{
    public function __construct(
        private YamlFilenameParameterValidator $yamlFilenameParameterValidator,
        private YamlParameterValidator $yamlParameterValidator,
        private Factory $parameterFactory,
    ) {
    }

    /**
     * @return array<class-string, array<mixed>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ControllerEvent::class => [
                ['validateStoreYamlFileRequest', 1000],
            ],
        ];
    }

    /**
     * @throws ErrorResponseException
     */
    public function validateStoreYamlFileRequest(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        if ('file_source_file_store' !== $route) {
            return;
        }

        $this->yamlFilenameParameterValidator->validate(
            $this->parameterFactory->createYamlFilenameParameter(
                'filename',
                $request->attributes->getString('filename')
            )
        );

        $this->yamlParameterValidator->validate(
            (new Parameter('content', trim($request->getContent())))
                ->withRequirements(new Requirements('yaml'))
        );
    }
}
