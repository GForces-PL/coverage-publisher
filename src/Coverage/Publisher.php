<?php

namespace Coverage;

interface Publisher
{
    /**
     * @param string $appName
     * @param float|array $coverage
     * @return string Result message
     */
    public function publish($appName, $coverage);
}
