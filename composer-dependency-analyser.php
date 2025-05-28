<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;

// command: composer check-deps
// see https://github.com/shipmonk-rnd/composer-dependency-analyser

$config = new Configuration();

return $config
    // Adjusting scanned paths
    ->addPathToScan(__DIR__ . '/src', isDev: false);
