<?php

namespace Coverage;

interface Publisher
{
    /**
     * @param string $a1NotationRange
     * @param float|array $coverage
     * @return string Result message
     */
    public function publish($a1NotationRange, $coverage);
}
