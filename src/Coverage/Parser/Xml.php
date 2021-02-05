<?php

namespace Coverage\Parser;

class Xml
{
    const COVERED_LINES = 'coveredstatements';
    const ALL_LINES = 'statements';

    /**
     * @param string $path
     * @return Result
     */
    public function parse($path)
    {
        $xml = simplexml_load_file($path);
        $metrics = $xml->project->metrics;
        return new Result((int) $metrics[self::COVERED_LINES], (int) $metrics[self::ALL_LINES]);
    }
}