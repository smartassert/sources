<?php

declare(strict_types=1);

namespace App\Migrations\Factory;

use App\Migrations\DependsOnServices;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MigrationFactoryDecorator implements MigrationFactory
{
    public function __construct(
        private MigrationFactory $migrationFactory,
        private ContainerInterface $container
    ) {
    }

    /**
     * @throws \ReflectionException
     */
    public function createVersion(string $migrationClassName): AbstractMigration
    {
        $instance = $this->migrationFactory->createVersion($migrationClassName);

        $serviceDependencies = $this->getMigrationServiceDependencies($instance);
        foreach ($serviceDependencies as $methodName => $serviceId) {
            $service = $this->container->get($serviceId);

            if (is_object($service)) {
                $this->setMigrationServiceDependency($instance, $methodName, $service);
            }
        }

        return $instance;
    }

    /**
     * @throws \ReflectionException
     *
     * @return string[]
     */
    private function getMigrationServiceDependencies(AbstractMigration $migration): array
    {
        $serviceIds = [];

        $reflectionClass = new \ReflectionClass($migration::class);
        $dependsOnServicesAttributes = $reflectionClass->getAttributes(DependsOnServices::class);

        if (0 !== count($dependsOnServicesAttributes)) {
            $dependsOnServicesAttribute = $dependsOnServicesAttributes[0];
            $arguments = $dependsOnServicesAttribute->getArguments();
            $serviceNamesArgument = $arguments[0] ?? [];

            if (is_array($serviceNamesArgument)) {
                foreach ($serviceNamesArgument as $key => $value) {
                    if (is_string($key) && is_string($value)) {
                        $serviceIds[$key] = $value;
                    }
                }
            }
        }

        return $serviceIds;
    }

    /**
     * @throws \ReflectionException
     */
    private function setMigrationServiceDependency(
        AbstractMigration $migration,
        string $methodName,
        object $service
    ): void {
        $reflectionClass = new \ReflectionClass($migration::class);

        if ($reflectionClass->hasMethod($methodName)) {
            $reflectionMethod = new \ReflectionMethod($migration::class, $methodName);

            $parameters = $reflectionMethod->getParameters();
            if (is_array($parameters) && 0 !== count($parameters)) {
                $parameter = $parameters[0];
                $type = $parameter->getType();

                if ($type instanceof \ReflectionNamedType) {
                    $typeName = $type->getName();

                    if ($service instanceof $typeName) {
                        $migration->{$methodName}($service);
                    }
                }
            }
        }
    }
}
