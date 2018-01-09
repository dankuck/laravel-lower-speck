<?php

namespace LowerSpeck;

use TestCase;
use Mockery;

class AnalyzerTest extends TestCase
{

    public function testInstantiate()
    {
        $spec = Mockery::mock(Specification::class);
        $grepper = Mockery::mock(ReferenceGrepper::class);
        new Analyzer($spec, $grepper);
    }

    private function buildGrepper(array $map) : ReferenceGrepper
    {
        $grepper = Mockery::mock(ReferenceGrepper::class);
        foreach ($map as $id) {
            $grepper->shouldReceive('hasReferenceTo')
                ->with($id)
                ->once()
                ->andReturn(true);
        }
        $grepper->shouldReceive('hasReferenceTo')
            ->andReturn(false);
        return $grepper;
    }

    /**
     * @LWR 1.g. The command must output a description of the code's 
     * references to the requirements.
     *
     * @LWR 1.g.b. The command must output progress as the percentage of 
     * requirements that have been addressed.
     * 
     * @LWR 1.g.c. The command must output the number of requirements that are 
     * not obsolete.
     *
     * @LWR 1.g.d. The command must output the number of requirements that 
     * have been addressed and are not obsolete.
     *
     * @LWR 1.g.e. The command should output the number of requirements that 
     * are obsolete.
     *
     * @LWR 1.g.f.c. In double-verbose mode and above the command must output 
     * all requirements.
     *
     * @LWR 1.g.g.h. The command must output a flag with any requirements that 
     * are not addressed.
     *
     * @LWR 1.g.g.c. The command must output a warning with any requirements 
     * that are incomplete.
     *
     * @LWR 1.g.g.i. The command must output a flag with any requirements that 
     * are obsoleted.
     *
     * @LWR 1.g.g.a. The command must output a warning with any requirements 
     * that do not use the keywords defined in RFC 2119.
     *
     */
    public function testGetAnalysisX()
    {
        $requirements = [
            new Requirement('1. (X) Must love dogs'),
            new Requirement('2. (I) John Tucker must die'),
            new Requirement('  2.a. This must be the place'),
            '',
            new Requirement('3. The gods must be crazy'),
            new Requirement('  3.a. Funny you should ask'),
            new Requirement('  3.b. It shood happen to you'),
        ];

        $spec = new Specification($requirements);
        $grepper = $this->buildGrepper(['2.', '2.a.', '3.']);

        $analyzer = new Analyzer($spec, $grepper);
        $analysis = $analyzer->getAnalysis();

        $this->assertEquals(60, $analysis->progress);
        $this->assertEquals(5, $analysis->active);
        $this->assertEquals(3, $analysis->addressed);
        $this->assertEquals(1, $analysis->obsolete);
        $this->assertEquals(1, $analysis->rfc2119WarningCount);
        $this->assertEquals(0, $analysis->customFlagWarningCount);

        $this->assertEquals(
            new RequirementAnalysis([
                'is_obsolete' => true, 
                'is_inactive' => true,
                'line' => '1. (X) Must love dogs'
            ]), 
            $analysis->requirements[0]
        );
        $this->assertEquals(
            new RequirementAnalysis([
                'is_incomplete' => true, 
                'line' => '2. (I) John Tucker must die'
            ]), 
            $analysis->requirements[1]
        );
        $this->assertEquals(
            new RequirementAnalysis([
                'line' => '  2.a. This must be the place'
            ]), 
            $analysis->requirements[2]
        );
        $this->assertEquals(
            new RequirementAnalysis([ 
                'is_inactive' => true,
            ]), 
            $analysis->requirements[3]
        );
        $this->assertEquals(
            new RequirementAnalysis(['line' => '3. The gods must be crazy']), 
            $analysis->requirements[4]
        );
        $this->assertEquals(
            new RequirementAnalysis([
                'is_pending' => true, 
                'line' => '  3.a. Funny you should ask'
            ]), 
            $analysis->requirements[5]
        );
        $this->assertEquals(
            new RequirementAnalysis([
                'is_pending' => true, 
                'has_warning' => true, 
                'notes' => ['Well-written requirements use RFC 2119 keywords such as MUST, SHOULD, and MAY'],
                'line' => "  3.b. It shood happen to you"
            ]),
            $analysis->requirements[6]
        );
    }

    /**
     * @LWR 1.g. The command must output a description of the code's 
     * references to the requirements.
     *
     * @LWR 1.g.b. The command must output progress as the percentage of 
     * requirements that have been addressed.
     * 
     * @LWR 1.g.c. The command must output the number of requirements that are 
     * not obsolete.
     *
     * @LWR 1.g.d. The command must output the number of requirements that 
     * have been addressed and are not obsolete.
     *
     * @LWR 1.g.e. The command should output the number of requirements that 
     * are obsolete.
     *
     * @LWR 1.g.f.c. In double-verbose mode and above the command must output 
     * all requirements.
     *
     * @LWR 1.g.g.h. The command must output a flag with any requirements that 
     * are not addressed.
     *
     * @LWR 1.g.g.b. The command must output a warning with any requirements 
     * that use unknown flags that do not begin with a dash (-).
     * 
     * @LWR 1.g.i. The command should output the number of warnings due to 
     * unexpected custom flags.
     */
    public function testReportBadCustomFlag()
    {
        $requirements = [new Requirement('1. (A) Must love dogs')];

        $spec = new Specification($requirements);

        $analyzer = new Analyzer($spec, $this->buildGrepper([]));
        $analysis = $analyzer->getAnalysis();

        $this->assertEquals(0, $analysis->progress);
        $this->assertEquals(1, $analysis->active);
        $this->assertEquals(0, $analysis->addressed);
        $this->assertEquals(0, $analysis->obsolete);
        $this->assertEquals(1, $analysis->customFlagWarningCount);

        $this->assertEquals(
            new RequirementAnalysis([
                'line' => '1. (A) Must love dogs',
                'is_pending' => true,
                'has_warning' => true,
                'notes' => ['Custom flags should start with a dash (-)'],
            ]),
            $analysis->requirements[0]
        );
    }

    /**
     * @LWR 1.g. The command must output a description of the code's 
     * references to the requirements.
     *
     * @LWR 1.g.b. The command must output progress as the percentage of 
     * requirements that have been addressed.
     * 
     * @LWR 1.g.c. The command must output the number of requirements that are 
     * not obsolete.
     *
     * @LWR 1.g.d. The command must output the number of requirements that 
     * have been addressed and are not obsolete.
     *
     * @LWR 1.g.e. The command should output the number of requirements that 
     * are obsolete.
     *
     * @LWR 1.g.f.c. In double-verbose mode and above the command must output 
     * all requirements.
     *
     * @LWR 1.g.g.d. The command must output an error with any requirements 
     * that cannot be parsed.
     * 
     * @LWR 1.g.j. The command should output the number of errors due to 
     * failure to parse.
     */
    public function testReportBadParse()
    {
        $requirements = [
            new Requirement('1. Must love dogs'),
            new Requirement('1.a Must love dogs'),
            new Requirement('1.b. Must love dogs'),
        ];

        $spec = new Specification($requirements);

        $analyzer = new Analyzer($spec, $this->buildGrepper([]));
        $analysis = $analyzer->getAnalysis();

        $this->assertEquals(0, $analysis->progress);
        $this->assertEquals(2, $analysis->active);
        $this->assertEquals(0, $analysis->addressed);
        $this->assertEquals(0, $analysis->obsolete);
        $this->assertEquals(1, $analysis->parseFailureCount);

        $this->assertEquals(
            new RequirementAnalysis([
                'line'       => '1. Must love dogs',
                'is_pending' => true,
            ]),
            $analysis->requirements[0]
        );
        $this->assertEquals(
            new RequirementAnalysis([
                'line'        => '1.a Must love dogs',
                'has_error'   => true,
                'is_inactive' => true,
                'notes'       => ['Cannot Parse Requirement'],
            ]),
            $analysis->requirements[1]
        );
        $this->assertEquals(
            new RequirementAnalysis([
                'line'       => '1.b. Must love dogs',
                'is_pending' => true,
            ]),
            $analysis->requirements[2]
        );
    }

    /**
     * @LWR 1.g. The command must output a description of the code's 
     * references to the requirements.
     *
     * @LWR 1.g.b. The command must output progress as the percentage of 
     * requirements that have been addressed.
     * 
     * @LWR 1.g.c. The command must output the number of requirements that are 
     * not obsolete.
     *
     * @LWR 1.g.d. The command must output the number of requirements that 
     * have been addressed and are not obsolete.
     *
     * @LWR 1.g.e. The command should output the number of requirements that 
     * are obsolete.
     *
     * @LWR 1.g.f.c. In double-verbose mode and above the command must output 
     * all requirements.
     *
     * @LWR 1.g.g.h. The command must output a flag with any requirements that 
     * are not addressed.
     *
     * @LWR 1.g.g.e. The command must output an error with any requirement 
     * immediately following a gap.
     *
     * @LWR 1.g.g.g. The command must output an error with any requirement 
     * that is out of order.
     *
     * @LWR 1.g.k. The command should output the number of errors due to gaps.
     * 
     * @LWR 1.g.m. The command should output the number of errors due to 
     * requirements being out of order.
     */
    public function testReportGapError()
    {
        $requirements = [
            new Requirement('1. Must love dogs'),
            new Requirement('1.b. Must hot dog'),
            new Requirement('1.c. Must hot pants'),
        ];

        $spec = new Specification($requirements);

        $analyzer = new Analyzer($spec, $this->buildGrepper([]));
        $analysis = $analyzer->getAnalysis();

        $this->assertEquals(0, $analysis->progress);
        $this->assertEquals(3, $analysis->active);
        $this->assertEquals(0, $analysis->addressed);
        $this->assertEquals(0, $analysis->obsolete);
        $this->assertEquals(1, $analysis->gapErrorCount);

        $this->assertEquals(
            new RequirementAnalysis([
                'line' => '1. Must love dogs',
                'is_pending' => true,
            ]),
            $analysis->requirements[0]
        );
        $this->assertEquals(
            new RequirementAnalysis([
                'line' => '1.b. Must hot dog',
                'is_pending' => true,
                'has_error' => true,
                'notes' => ['This requirement is out of order or the previous requirement is missing'],
            ]),
            $analysis->requirements[1]
        );
        $this->assertEquals(
            new RequirementAnalysis([
                'line' => '1.c. Must hot pants',
                'is_pending' => true,
            ]),
            $analysis->requirements[2]
        );

    }

    /**
     * @LWR 1.g. The command must output a description of the code's 
     * references to the requirements.
     *
     * @LWR 1.g.b. The command must output progress as the percentage of 
     * requirements that have been addressed.
     * 
     * @LWR 1.g.c. The command must output the number of requirements that are 
     * not obsolete.
     *
     * @LWR 1.g.d. The command must output the number of requirements that 
     * have been addressed and are not obsolete.
     *
     * @LWR 1.g.e. The command should output the number of requirements that 
     * are obsolete.
     *
     * @LWR 1.g.f.c. In double-verbose mode and above the command must output 
     * all requirements.
     *
     * @LWR 1.g.g.h. The command must output a flag with any requirements that 
     * are not addressed.
     *
     * @LWR 1.g.g.f. The command must output an error with any requirement 
     * with an ID that is duplicated.
     * 
     * @LWR 1.g.l. The command should output the number of duplicate ID's.
     */
    public function testReportDupeId()
    {
        $requirements = [
            new Requirement('1. Must love dogs'),
            new Requirement('1.a. Must hot dog'),
            new Requirement('1.a. Must hot pants'),
        ];

        $spec = new Specification($requirements);

        $analyzer = new Analyzer($spec, $this->buildGrepper([]));
        $analysis = $analyzer->getAnalysis();

        $this->assertEquals(0, $analysis->progress);
        $this->assertEquals(3, $analysis->active);
        $this->assertEquals(0, $analysis->addressed);
        $this->assertEquals(0, $analysis->obsolete);
        $this->assertEquals(2, $analysis->duplicateIdErrorCount);

        $this->assertEquals(
            new RequirementAnalysis([
                'line' => '1. Must love dogs',
                'is_pending' => true,
            ]),
            $analysis->requirements[0]
        );
        $this->assertEquals(
            new RequirementAnalysis([
                'line' => '1.a. Must hot dog',
                'is_pending' => true,
                'has_error' => true,
                'notes' => ['Duplicate ID'],
            ]),
            $analysis->requirements[1]
        );
        $this->assertEquals(
            new RequirementAnalysis([
                'line' => '1.a. Must hot pants',
                'is_pending' => true,
                'has_error' => true,
                'notes' => ['This requirement is out of order or the previous requirement is missing', 'Duplicate ID'],
            ]),
            $analysis->requirements[2]
        );
    }

    /**
     * @LWR 1.g. The command must output a description of the code's 
     * references to the requirements.
     *
     * @LWR 1.g.b. The command must output progress as the percentage of 
     * requirements that have been addressed.
     * 
     * @LWR 1.g.c. The command must output the number of requirements that are 
     * not obsolete.
     *
     * @LWR 1.g.d. The command must output the number of requirements that 
     * have been addressed and are not obsolete.
     *
     * @LWR 1.g.e. The command should output the number of requirements that 
     * are obsolete.
     *
     * @LWR 1.g.f.c. In double-verbose mode and above the command must output 
     * all requirements.
     *
     * @LWR 1.g.g.h. The command must output a flag with any requirements that 
     * are not addressed.
     *
     * @LWR 1.g.g.c. The command must output a warning with any requirements 
     * that are incomplete.
     *
     * @LWR 1.g.g.i. The command must output a flag with any requirements that 
     * are obsoleted.
     *
     * @LWR 1.g.g.a. The command must output a warning with any requirements 
     * that do not use the keywords defined in RFC 2119.
     *
     * @LWR 1.f.a. If an ID was supplied as an argument, the command MAY only 
     * search for that requirement and its sub-requirements.
     * 
     * @LWR 1.g.a. If an ID was supplied as an argument, the command MUST only 
     * give output relative to that requirement and its sub-requirements.
     */
    public function testGetAnalysisById()
    {
        $requirements = [
            new Requirement('1. (X) Must love dogs'),
            new Requirement('2. (I) John Tucker must die'),
            new Requirement('  2.a. This must be the place'),
            '',
            new Requirement('3. The gods must be crazy'),
            new Requirement('  3.a. Funny you should ask'),
            new Requirement('  3.b. It shood happen to you'),
        ];

        $spec = new Specification($requirements);
        $grepper = $this->buildGrepper(['3.']);
        $id = 3;

        $analyzer = new Analyzer($spec, $grepper);
        $analysis = $analyzer->getAnalysis($id);

        $this->assertEquals(33, $analysis->progress);
        $this->assertEquals(3, $analysis->active);
        $this->assertEquals(1, $analysis->addressed);
        $this->assertEquals(0, $analysis->obsolete);
        $this->assertEquals(1, $analysis->rfc2119WarningCount);
        $this->assertEquals(0, $analysis->customFlagWarningCount);

        $this->assertEquals(
            new RequirementAnalysis(['line' => '3. The gods must be crazy']), 
            $analysis->requirements[0]
        );
        $this->assertEquals(
            new RequirementAnalysis([
                'is_pending' => true, 
                'line' => '  3.a. Funny you should ask'
            ]), 
            $analysis->requirements[1]
        );
        $this->assertEquals(
            new RequirementAnalysis([
                'is_pending' => true, 
                'has_warning' => true, 
                'notes' => ['Well-written requirements use RFC 2119 keywords such as MUST, SHOULD, and MAY'],
                'line' => "  3.b. It shood happen to you"
            ]),
            $analysis->requirements[2]
        );
    }

}
