<?php

namespace Exo\Util;

use Exo\History;
use InvalidArgumentException;

class Finder
{

    /**
     * @var array
     */
    private array $context;

    /**
     * Finder constructor.
     * @param array $context
     */
    public function __construct(array $context = [])
    {
        $this->context = $context;
    }

    /**
     * Builds a migration history from a filesystem path.
     *
     * @param string $path
     * @return History
     */
    public function fromPath(string $path): History
    {
        $path = realpath(rtrim($path, '/'));

        if (!is_dir($path)) {
            throw new InvalidArgumentException(sprintf('%s is not a valid directory.', $path));
        }

        $history = new History();
        $entries = scandir($path);

        foreach ($entries as $entry) {
            $entryPath = sprintf('%s/%s', $path, $entry);

            if (is_dir($entryPath)) {
                continue;
            }

            $version = pathinfo($entry, PATHINFO_FILENAME);
            $migration = $this->requireFile($path . '/' . $entry);

            $history->add($version, $migration);
        }

        return $history;
    }

    /**
     * Requires a file with context extracted into the local symbol table.
     *
     * @param string $filepath
     * @return mixed
     */
    public function requireFile(string $filepath)
    {
        if (!empty($this->context)) {
            extract($this->context);
        }
        return require($filepath);
    }
}
