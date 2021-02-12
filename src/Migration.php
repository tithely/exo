<?php

namespace Exo;

/**
 * Class Migration
 *
 * This class is for backwards compatibility, so that previous users need not immediately refactor all previous
 * implementations/instances of Migration() to TableMigrations().
 *
 * @deprecated
 */
final class Migration extends TableMigration
{}
