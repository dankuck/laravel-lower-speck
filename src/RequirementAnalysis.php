<?php

namespace LowerSpeck;

class RequirementAnalysis
{

    public $line;
    public $is_inactive;
    public $has_error;
    public $is_obsolete;
    public $is_incomplete;
    public $has_warning;
    public $is_pending;
    public $notes;

    public function __construct(array $fields)
    {
        $this->line          = $fields['line'] ?? '';
        $this->is_inactive   = $fields['is_inactive'] ?? false;
        $this->has_error     = $fields['has_error'] ?? false;
        $this->is_obsolete   = $fields['is_obsolete'] ?? false;
        $this->is_incomplete = $fields['is_incomplete'] ?? false;
        $this->has_warning   = $fields['has_warning'] ?? false;
        $this->is_pending    = $fields['is_pending'] ?? false;
        $this->notes         = $fields['notes'] ?? [];
    }
}
