<?php

namespace LowerSpeck;

use Illuminate\Console\Command;

class Reporter
{
    private $analysis;
    private $verbosity;

    const NORMAL       = 0;
    const VERBOSE      = 1;
    const VERY_VERBOSE = 2;

    public function __construct(Analysis $analysis, int $verbosity)
    {
        $this->analysis = $analysis;
        $this->verbosity = $verbosity;
    }

    public function report(Command $command)
    {
        $previous_was_blank = null;
        $table_data = collect($this->analysis->requirements)
            // Filter out according to verbosity
            ->filter(function ($requirement_analysis) {
                if (!trim($requirement_analysis->line)) {
                    return true;
                }
                if ($this->verbosity === static::NORMAL) {
                    if ($requirement_analysis->is_obsolete) {
                        return false;
                    }
                    if ($requirement_analysis->is_pending) {
                        return true;
                    }
                    if ($requirement_analysis->is_incomplete) {
                        return true;
                    }
                    return false;
                }
                if ($this->verbosity === static::VERBOSE) {
                    if ($requirement_analysis->is_obsolete) {
                        return false;
                    }
                    return true;
                }
                return true;
            })
            // Filter out double-blank-lines
            ->filter(function ($requirement_analysis) use (&$previous_was_blank) {
                $is_blank = trim($requirement_analysis->line) == '';
                if ($is_blank && $previous_was_blank) {
                    return false;
                }
                $previous_was_blank = $is_blank;
                return true;
            })
            // Turn analysis entries into arrays compatible with `table()`
            ->map(function ($requirement_analysis) {
                $line = array_merge(
                    [$requirement_analysis->line],
                    $requirement_analysis->notes
                );
                $flags = [];
                if ($requirement_analysis->is_obsolete) {
                    $flags[] = 'X';
                }
                if ($requirement_analysis->is_pending) {
                    $flags[] = '-';
                }
                if ($requirement_analysis->has_warning) {
                    $flags[] = '?';
                }
                if ($requirement_analysis->has_error) {
                    $flags[] = '!';
                }
                if ($requirement_analysis->is_incomplete) {
                    $flags[] = 'I';
                }
                return [implode('', $flags), implode("\n", $line)];
            })
            ->values()
            ->all();

        // remove leading blank
        if (!trim($table_data[0][1])) {
            array_shift($table_data);
        }

        // remove trailing blank
        if (!trim($table_data[count($table_data) - 1][1])) {
            array_pop($table_data);
        }

        $command->table(
            ['State', 'Requirement'],
            $table_data
        );

        $command->info("Progress: {$this->analysis->progress}%");
        $command->info("Requirements: {$this->analysis->active}");
        $command->info("Addressed: {$this->analysis->addressed}");
        $command->info("Obsolete: {$this->analysis->obsolete}");

        if ($this->analysis->rfc2119WarningCount) {
            $command->comment(
                $this->analysis->rfc2119WarningCount == 1
                ? "1 requirement uses weak language."
                : "{$this->analysis->rfc2119WarningCount} requirements use weak language."
            );
        }

        if ($this->analysis->customFlagWarningCount) {
            $command->comment(
                $this->analysis->customFlagWarningCount == 1
                ? "1 requirement uses bad flags."
                : "{$this->analysis->customFlagWarningCount} requirements use bad flags."
            );
        }

        if ($this->analysis->parseFailureCount) {
            $command->error(
                $this->analysis->parseFailureCount == 1
                ? "1 requirement cannot be parsed."
                : "{$this->analysis->parseFailureCount} requirements cannot be parsed."
            );
        }

        if ($this->analysis->gapErrorCount) {
            $command->error(
                $this->analysis->gapErrorCount == 1
                ? "1 requirement is out of order."
                : "{$this->analysis->gapErrorCount} requirements are out of order."
            );
        }

        if ($this->analysis->duplicateIdErrorCount) {
            $command->error(
                $this->analysis->duplicateIdErrorCount == 1
                ? "1 requirement uses a duplicate ID."
                : "{$this->analysis->duplicateIdErrorCount} requirements use duplicate IDs."
            );
        }

        $command->line('Use -v or -vv to see more information.');
    }

}

