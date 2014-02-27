<?php

namespace mvodanovic\WebFW\Database\TableColumns;

use mvodanovic\WebFW\Database\Table;

class IntegerColumn extends Column
{
    public function __construct(Table $table, $name, $nullable = true, $precision = null)
    {
        parent::__construct($table, $name, static::TYPE_INTEGER, $nullable, $precision);
    }
}
