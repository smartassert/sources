<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    SmartAssert\UsersSecurityBundle\UsersSecurityBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    League\FlysystemBundle\FlysystemBundle::class => ['all' => true],
    SmartAssert\HealthCheckBundle\HealthCheckBundle::class => ['all' => true],
    SmartAssert\TestAuthenticationProviderBundle\TestAuthenticationProviderBundle::class => ['test' => true],
    SmartAssert\WorkerMessageFailedEventBundle\WorkerMessageFailedEventBundle::class => ['all' => true],
];
