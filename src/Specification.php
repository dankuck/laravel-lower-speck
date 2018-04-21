<?php

namespace LowerSpeck;

class Specification
{
    private $lines;
    private $byId;

    public function __construct(array $lines)
    {
        $this->lines = $lines;
        $this->byId = [];
        foreach ($this->getRequirements() as $requirement) {
            $this->byId[strtolower($requirement->id)][] = $requirement;
        }
    }

    public function getRequirements() : array
    {
        return array_values(array_filter($this->lines, function ($line) {
            return $line instanceof Requirement;
        }));
    }

    public function getAll() : array
    {
        return $this->lines;
    }

    public function getByIdWithChildren(string $id, bool $include_all_parse_errors = false) : array
    {
        $requirements = array_values(array_filter($this->lines, function ($line) use ($id, $include_all_parse_errors) {
            if (!$line instanceof Requirement) {
                return true;
            }
            if (substr($line->id, 0, strlen($id)) === $id) {
                return true;
            }
            if ($include_all_parse_errors && $line->hasParseError()) {
                return true;
            }
            return false;
        }));
        // now to trim them
        while (is_string(reset($requirements))) {
            array_shift($requirements);
        }
        while (is_string(end($requirements))) {
            array_pop($requirements);
        }
        return $requirements;
    }

    /**
     * Tells whether there is more than one requirement identified by the id.
     * If there are none, this returns false.
     * @param  string $id
     * @return bool     
     */
    public function idIsDuplicate(string $id) : bool
    {
        $id = strtolower($id);
        return isset($this->byId[$id]) && count($this->byId[$id]) > 1; 
    }
}
