<?php

namespace LowerSpeck;

class ReferenceGrepper
{
    private $paths;
    private $references = null;
    private $grep_interface = null;

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

    /**
     * On BSD-type machines (ie, OSX), the -E flag is appropriate to match the
     * Regular Expression we want. On GNU-type machines, the -E flag won't 
     * work but the -P flag will.
     * @return string, either BSD or GNU
     */
    private function detectGrepInterface() : string
    {
        if ($this->grep_interface) {
            return $this->grep_interface;
        }

        $gnu_style = `echo " LWR  1" | grep -P '\\bLWR\\s+\\d' 2>&1`;
        if (trim($gnu_style) == 'LWR  1') {
            $this->grep_interface = 'GNU';
            return $this->grep_interface;
        }

        $bsd_style = `echo " LWR  1" | grep -E '\\bLWR\\s+\\d' 2>&1`;
        if (trim($bsd_style) == 'LWR  1') {
            $this->grep_interface = 'BSD';
            return $this->grep_interface;
        }

        throw new \Exception('Could not detect grep version.');
    }

    private function buildCommand(string $path) : string
    {
        $interface = $this->detectGrepInterface();
        $query = escapeshellarg('\\bLWR\\s+\\d');
        $dir = escapeshellarg($path);
        if ($interface == 'GNU') {
            return "grep -P {$query} {$dir} -R";
        }
        if ($interface == 'BSD') {
            return "grep -E {$query} {$dir} -R";
        }

        throw new \Exception('Unrecognized grep interface: ' . $interface . '.');
    }

    private function grep()
    {
        $this->references = [];
        foreach ($this->paths as $path) {
            $command = $this->buildCommand($path);
            $lines = `$command`;
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
