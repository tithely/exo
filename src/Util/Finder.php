<?php

namespace Exo\Util;

use Exo\History;

class Finder
{
    /**
     * Builds a migration history from a filesystem path.
     *
     * @param string $path
     * @return History
     */
    public function fromPath(string $path)
    {
        $path = realpath(rtrim($path, '/'));

        if (!is_dir($path)) {
            throw new \InvalidArgumentException(sprintf('%s is not a valid directory.', $path));
        }

        $history = new History();
        $entries = scandir($path);

        foreach ($entries as $entry) {
            $entryPath = sprintf('%s/%s', $path, $entry);

            if (is_dir($entryPath)) {
                continue;
            }

            $version = pathinfo($entry, PATHINFO_FILENAME);
            $migration = require($path . '/' . $entry);

            $history->add($version, $migration);
        }

        return $history;
    }
}
