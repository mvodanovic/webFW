<?php

namespace mvodanovic\WebFW\Database\TableConstraints;

use mvodanovic\WebFW\Database\Table;

class Unique extends Constraint
{
    public function __construct(Table $table, $name = null)
    {
        parent::__construct($table, static::TYPE_UNIQUE, $name);
    }
}
