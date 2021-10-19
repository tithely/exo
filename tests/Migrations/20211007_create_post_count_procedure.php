<?php

return Exo\ProcedureMigration::create('post_count')
    ->withDeterminism(false)
    ->withDataUse('READS SQL DATA')
    ->withBody("SELECT 'Total Posts:', COUNT(*) FROM posts;");
