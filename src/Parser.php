<?php

namespace LowerSpeck;

class Parser
{
    private $filepath;

    public function __construct(string $filepath)
    {
        $this->filepath = $filepath;
    }

    public function getSpecification()
    {
        $contents = file_get_contents($this->filepath);

        $lines = preg_split('/\r?\n/', $contents);

        $items = [];

        foreach ($lines as $line) {
            if (trim($line)) {
                $items[] = new Requirement($line);
            } else {
                $items[] = $line;
            }
        }

        return new Specification($items);
    }
}
