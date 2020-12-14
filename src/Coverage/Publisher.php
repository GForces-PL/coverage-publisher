<?php

namespace Coverage;

interface Publisher
{
    /**
     * @param string $appName
     * @param float $coverage
     * @param array $options
     * @return string Result message
     */
    public function publish($appName, $coverage, array $options = []);
}