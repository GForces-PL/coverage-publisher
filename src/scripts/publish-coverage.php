<?php

date_default_timezone_set('Europe/London');
require_once __DIR__ . '/../../vendor/autoload.php';

if (count($argv) < 3) {
    echo <<<USAGE
Usage: php src/scripts/publish-coverage.php APP COVERAGE
   eg. php src/scripts/publish-coverage.php "NEM API" 97.09\n
USAGE;
    exit(1);
}

$publisher = new \Coverage\Publisher\GoogleSheets();
try {
    $message = $publisher->publish($argv[1], $argv[2]);
} catch (Exception $e) {
    $message = "Failed to publish coverage: {$e->getMessage()}";
}
echo "$message\n";
