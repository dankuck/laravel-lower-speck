<?php

namespace LowerSpeck;

class Analyzer
{
    private $specification;
    private $grepper;    

    public function __construct(Specification $specification, ReferenceGrepper $grepper)
    {
        $this->specification = $specification;
        $this->grepper = $grepper;
    }

    public function getAnalysis(string $super_id = null) : Analysis
    {
        $analysis = new Analysis();

        $last_id = '';
        $last_was_failed_parse = false;

        $requirements = $super_id 
            ? $this->specification->getByIdWithChildren($super_id, true)
            : $this->specification->getAll();

        $analysis->requirements = array_map(function ($requirement) use (&$last_id, &$last_was_failed_parse, $analysis, $super_id) {
            $requirement_analysis = new RequirementAnalysis([]);
            if ($requirement instanceof Requirement) {
                $requirement_analysis->line = $requirement->line;
                if ($requirement->hasParseError()) {
                    $analysis->parseFailureCount++;
                    $requirement_analysis->has_error = true;
                    $requirement_analysis->is_inactive = true;
                    $requirement_analysis->notes[] = "Cannot Parse Requirement";
                } elseif ($requirement->hasFlag('X')) {
                    $requirement_analysis->is_obsolete = true;
                    $requirement_analysis->is_inactive = true;
                } else {
                    if (!$this->grepper->hasReferenceTo($requirement->id)) {
                        $requirement_analysis->is_pending = true;
                    }
                    if ($requirement->hasFlag('I')) {
                        $requirement_analysis->is_incomplete = true;
                    }
                    if (!$requirement->hasRfc2119Keywords()) {
                        $analysis->rfc2119WarningCount++;
                        $requirement_analysis->has_warning = true;
                        $requirement_analysis->notes[] = 'Well-written requirements use RFC 2119 keywords such as MUST, SHOULD, and MAY';
                    }
                    if ($requirement->getBadFlags()) {
                        $analysis->customFlagWarningCount++;
                        $requirement_analysis->has_warning = true;
                        $requirement_analysis->notes[] = 'Custom flags should start with a dash (-)';
                    }
                    // If a particular id is sought, that one shouldn't follow 
                    // anything. We need to make sure the code doesn't mark it 
                    // as out of order.
                    $this_first_one_can_be_out_of_order = ($super_id && !$last_id && $super_id == $requirement->id);
                    if (!$this_first_one_can_be_out_of_order && !$last_was_failed_parse && !$requirement->follows($last_id)) {
                        $analysis->gapErrorCount++;
                        $requirement_analysis->has_error = true;
                        $requirement_analysis->notes[] = 'This requirement is out of order or the previous requirement is missing';
                    }
                    if ($this->specification->idIsDuplicate($requirement->id)) {
                        $analysis->duplicateIdErrorCount++;
                        $requirement_analysis->has_error = true;
                        $requirement_analysis->notes[] = "Duplicate ID";
                    }
                }
                $last_id = $requirement->id;
                $last_was_failed_parse = $requirement->hasParseError();
            } else {
                $requirement_analysis->is_inactive = true;
            }
            return $requirement_analysis;
        }, $requirements);

        foreach ($analysis->requirements as $requirement)
        {
            if ($requirement->is_obsolete) {
                $analysis->obsolete++;
                continue;
            }
            if (!$requirement->is_inactive) {
                $analysis->active++;
                if (!$requirement->is_pending) {
                    $analysis->addressed++;
                }
            }
        }
        
        $analysis->progress = $analysis->active ? floor(100 * $analysis->addressed / $analysis->active) : 0;

        return $analysis;
    }

}
