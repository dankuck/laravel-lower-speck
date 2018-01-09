<?php

namespace LowerSpeck;

class Requirement
{
    public $id;
    public $flags;
    public $description;
    public $line;

    private $parse_error = false;

    const KNOWN_FLAGS = ['I', 'X'];

    public function __construct(string $line)
    {
        $this->line = $line;

        $next = trim($line);

        list($id, $next) = preg_split('/\s+/', $next, 2);

        if (preg_match('/^\d+[a-z\.]*\.$/i', $id)) {
            $this->id = $id;
        } else {
            $this->parse_error = true;
            $this->flags = [];
            return;
        }

        if (preg_match('/^\((.*)\)/', $next, $flags)) {
            $this->flags = preg_split('/\s*,\s*/', $flags[1]);
            $next = trim(substr($next, strlen($flags[0])));
        } else {
            $this->flags = [];
        }

        $this->description = $next;
    }

    public function hasFlag(string $flag) : bool
    {
        return in_array($flag, $this->flags);
    }

    public function getBadFlags() : array
    {
        $custom = array_diff($this->flags, static::KNOWN_FLAGS);
        return array_values(array_filter($custom, function ($flag) { return $flag[0] != '-'; }));
    }

    public function hasRfc2119Keywords() : bool
    {
        $keywords = ['must', 'should', 'may', 'required', 'shall', 'recommended', 'optional'];
        foreach ($keywords as $keyword) {
            if (preg_match('/\b' . $keyword . '\b/i', $this->description)) {
                return true;
            }
        }
        return false;
    }

    public function hasParseError() : bool
    {
        return $this->parse_error;
    }

    public function follows(string $id) : bool
    {
        $my_parts = explode('.', strtolower($this->id));
        if (!end($my_parts)) {
            array_pop($my_parts); // ignore the blank at the end
        }

        $his_parts = explode('.', strtolower($id));
        if (!end($his_parts)) {
            array_pop($his_parts); // ignore the blank at the end
        }

        // Get my last part and his corresponding part. Ignore his trailing 
        // parts, if they exist. Let his corresponding part be blank if it
        // doesn't exist. We'll catch it soon.
        $my_last = $my_parts[count($my_parts) - 1];
        $his_match_to_my_last = $his_parts[count($my_parts) - 1] ?? '';

        // Get my beginning sequence and his corresponding sequence
        $my_start = array_slice($my_parts, 0, count($my_parts) - 1);
        $his_match_to_my_start = array_slice($his_parts, 0, count($my_parts) - 1);

        // If I follow him, we must have the same beginning sequence. At the 
        // top level, the beginning sequences should both be an empty array.
        if ($his_match_to_my_start != $my_start) {
            return false;
        }

        // If my last part is 1 or a, then I only follow my parent. If all his 
        // parts exactly match my beginning sequence, then he's my parent.
        if ($my_last === '1' || $my_last === 'a') {
            return $my_start == $his_parts;
        }

        // If my last part isn't 1 or a, then his matching part needs to be
        // just before my last part.
        // Let's use PHP's ++ rules to determine the next string.
        $his_match_to_my_last++; 
        return $my_last == $his_match_to_my_last;
    }
}
