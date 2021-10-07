<?php

return Exo\ProcedureMigration::create('post_count')
    ->isDeterministic(false)
    ->readsSqlData('READS SQL DATA')
    ->withBody("SELECT 'Total Posts:', COUNT(*) FROM posts;");
