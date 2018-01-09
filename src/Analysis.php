<?php

namespace LowerSpeck;

class Analysis
{

    public $active = 0;
    public $addressed = 0;
    public $obsolete = 0;
    public $progress = 0;
    public $requirements = [];
    public $rfc2119WarningCount = 0;
    public $customFlagWarningCount = 0;
    public $parseFailureCount = 0;
    public $gapErrorCount = 0;
    public $duplicateIdErrorCount = 0;
}
