<?php

// Note: A more likely use case is to throw an exception if context is undefined, but this works fine for a test case.
$tenant_database_name = $tenant_database_name ?? 'undefined';

return Exo\ViewMigration::alter('user_counts')
    ->withBody("SELECT COUNT(id) AS user_count FROM ${tenant_database_name}.users");
