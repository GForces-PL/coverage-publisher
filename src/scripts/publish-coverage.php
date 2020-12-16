<?php

date_default_timezone_set('Europe/London');

if (count($argv) < 3) {
    echo <<<USAGE
Usage: php src/scripts/publish-coverage.php APP_NAME CLOVER_XML_FILE_PATH
   eg. php src/scripts/publish-coverage.php "NEM API" /tmp/clover.xml\n
USAGE;
    exit(1);
}

$parser = new \Coverage\Parser\Xml();
$result = $parser->parse($argv[2]);
$publisher = new \Coverage\Publisher\GoogleSheets();
echo $publisher->publish($argv[1], $result->getPercentage()) . "\n";
