<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    // Define what rule sets will be applied
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_82,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        SetList::NAMING,
        SymfonySetList::SYMFONY_72,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
        DoctrineSetList::DOCTRINE_ORM_214,
        DoctrineSetList::DOCTRINE_CODE_QUALITY,
    ]);

    // Skip certain directories/files
    $rectorConfig->skip([
        __DIR__ . '/var',
        __DIR__ . '/vendor',
        __DIR__ . '/migrations',
        __DIR__ . '/config/bootstrap.php',
    ]);

    // Configure parallel processing
    $rectorConfig->parallel();

    // Prefer use statements over FQDN (default: true)
    $rectorConfig->importShortClasses(true);
    $rectorConfig->importNames(true);

    // Remove unused imports
    $rectorConfig->removeUnusedImports();
};