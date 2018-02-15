<?php

namespace LowerSpeck;

class ReferenceGrepper
{
    private $paths;
    private $references = null;

    public function __construct(array $paths) 
    {
        if (!$paths) {
            throw new \Exception('`paths` must not be an empty array.');
        }
        $this->paths = $paths;
    }

    public function hasReferenceTo(string $id) : bool
    {
        if ($this->references === null) {
            $this->grep();
        }
        return isset($this->references[strtolower($id)]);
    }

    private function grep()
    {
        $this->references = [];
        foreach ($this->paths as $path) {
            $query = escapeshellarg('\\bLWR\\s+\\d');
            $dir = escapeshellarg($path);
            $lines = `grep -E $query $dir -R`;
            foreach (preg_split('/\n/', $lines) as $line) {
                preg_match_all('/\bLWR\s+(\d+[\.a-z]+)/i', $line, $matches);
                foreach ($matches[1] as $match) {
                    $this->addReference($match, $line);
                }
            }
        }
    }

    /**
     * Make sure the parents of the id get added too.
     * @param string $match 
     * @param string $line  
     */
    private function addReference(string $match, string $line)
    {
        $parts = explode('.', strtolower($match));
        while (!end($parts)) {
            array_pop($parts); // throw away any trailing empties
        }
        // Add the id, then throw away the last part and add that, etc.
        do {
            $id = implode('.', $parts) . '.';
            $this->references[$id][] = $line;
            array_pop($parts);
        } while ($parts);
    }
}
