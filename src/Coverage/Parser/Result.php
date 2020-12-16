<?php

namespace Coverage\Parser;

class Result
{
    private $coveredLines;
    private $allLines;

    /**
     * @param int $coveredLines
     * @param int $allLines
     */
    public function __construct($coveredLines, $allLines)
    {
        $this->coveredLines = $coveredLines;
        $this->allLines = $allLines;
    }

    /**
     * @param int $precision
     * @return float
     */
    public function getPercentage($precision = 2)
    {
        return $this->allLines ? round($this->coveredLines / $this->allLines * 100, $precision) : 0.0;
    }

    /**
     * @return string
     */
    public function getLinesRatio()
    {
        return "$this->coveredLines/$this->allLines";
    }
}