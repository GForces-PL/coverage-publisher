<?php

namespace Coverage;

interface Publisher
{
    /**
     * @param string $appName
     * @param float $coverage
     * @return string Result message
     */
    public function publish($appName, $coverage);
}
