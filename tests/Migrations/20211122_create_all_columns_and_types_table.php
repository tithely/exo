<?php

return Exo\TableMigration::create('all_columns_and_types')
    // mysql
    ->addColumn('primary_col', ['type' => 'integer', 'primary' => true, 'auto_increment' => true, 'null' => false])
    ->addColumn('enum_col', ['type' => 'enum', 'values' => ['a', 'b', 'c']])
    ->addColumn('datetime_col', ['type' => 'datetime'])
    // pgsql
    ->addColumn('serial_col', ['type' => 'serial'])
    // shared
    ->addColumn('bool_col', ['type' => 'bool'])
    ->addColumn('char_col', ['type' => 'char', 'length' => 5])
    ->addColumn('date_col', ['type' => 'date'])
    ->addColumn('decimal_col', ['type' => 'decimal', 'precision' => 2, 'scale' => 2])
    ->addColumn('integer_col', ['type' => 'integer'])
    ->addColumn('json_col', ['type' => 'json'])
    ->addColumn('string_col', ['type' => 'string', 'length' => 64])
    ->addColumn('text_col', ['type' => 'text', 'length' => 65535])
    ->addColumn('timestamp_col', ['type' => 'timestamp'])
    ->addColumn('uuid_col', ['type' => 'uuid'])
    ->addIndex('all_double_index', ['primary_col', 'serial_col']);
