<?php

namespace LowerSpeck;

use TestCase;
use Mockery;
use Illuminate\Console\Command;

class ReporterTest extends TestCase
{

    public function testInstantiate()
    {
        new Reporter(new Analysis([]), Reporter::NORMAL);
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
     * @LWR 1.g.h. The command should output the number of warnings due to 
     * missing RFC 2119 keywords.
     * 
     * @LWR 1.g.i. The command should output the number of warnings due to 
     * unexpected custom flags.
     *
     * @LWR 1.g.j. The command should output the number of errors due to 
     * failure to parse.
     *
     * @LWR 1.g.k. The command should output the number of errors due to gaps.
     * 
     * @LWR 1.g.m. The command should output the number of errors due to 
     * requirements being out of order.
     *
     * @LWR 1.g.g.d.a. The command MUST output parse errors even for 
     * requirements that do not fall within the super ID supplied to the 
     * command.
     */
    public function testReportAFewThings_VeryVerbose()
    {
        $analysis = new Analysis();
        $analysis->progress = 60;
        $analysis->active = 5;
        $analysis->addressed = 3;
        $analysis->obsolete = 1;
        $analysis->requirements = [
            new RequirementAnalysis([
                'line' => '1. (X) Must love dogs',
                'is_obsolete' => true,
            ]),
            new RequirementAnalysis([
                'line' => '2. (I) John Tucker must die',
                'is_incomplete' => true,
            ]),
            new RequirementAnalysis([
                'line' => '  2.a. This must be the place',
            ]),

            new RequirementAnalysis([]),

            new RequirementAnalysis([
                'line' => '3. The gods must be crazy',
            ]),
            new RequirementAnalysis([
                'line' => '  3.a. Funny you should ask',
                'is_pending' => true,
            ]),  
            new RequirementAnalysis([
                'line' => '  3.b. It shood happen to you',
                'is_pending' => true,
                'has_warning' => true,
                'notes' => ['Well-written requirements use RFC 2119 keywords such as MUST, SHOULD, and MAY'],
            ]),

            new RequirementAnalysis([]),

            new RequirementAnalysis([
                'line'            => 'PARSE ERROR',
                'has_parse_error' => true,
            ]),
        ];
        $analysis->rfc2119WarningCount = 1;
        $analysis->customFlagWarningCount = 2;
        $analysis->parseFailureCount = 3;
        $analysis->gapErrorCount = 4;

        $reporter = new Reporter($analysis, Reporter::VERY_VERBOSE);

        $command = Mockery::mock(Command::class);
        $command->shouldReceive('line')
            ->with('Use -v or -vv to see more information.');
        $command->shouldReceive('info')
            ->with('Progress: 60%')
            ->once();
        $command->shouldReceive('info')
            ->with('Requirements: 5')
            ->once();
        $command->shouldReceive('info')
            ->with('Addressed: 3')
            ->once();
        $command->shouldReceive('info')
            ->with('Obsolete: 1')
            ->once();

        $command->shouldReceive('comment')
            ->with('1 requirement uses weak language.')
            ->once();
        $command->shouldReceive('comment')
            ->with('2 requirements use bad flags.')
            ->once();
        $command->shouldReceive('error')
            ->with('3 requirements cannot be parsed.')
            ->once();
        $command->shouldReceive('error')
            ->with('4 requirements are out of order.')
            ->once();

        $command->shouldReceive('table')
            ->with(
                ['State', 'Requirement'],
                [
                    ['X', '1. (X) Must love dogs'],
                    ['I', '2. (I) John Tucker must die'],
                    ['', '  2.a. This must be the place'],
                    ['', ''],
                    ['', '3. The gods must be crazy'],
                    ['-', '  3.a. Funny you should ask'],
                    ['-?', "  3.b. It shood happen to you\nWell-written requirements use RFC 2119 keywords such as MUST, SHOULD, and MAY"],
                    ['', ''],
                    ['', 'PARSE ERROR'],
                ]
            )
            ->once();

        $reporter->report($command);
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
     * @LWR 1.g.g.a. The command must output a warning with any requirements 
     * that do not use the keywords defined in RFC 2119.
     *
     * @LWR 1.g.g.b. The command must output a warning with any requirements 
     * that use unknown flags that do not begin with a dash (-).
     *
     * @LWR 1.g.g.c. The command must output a warning with any requirements 
     * that are incomplete.
     * 
     * @LWR 1.g.g.d. The command must output an error with any requirements 
     * that cannot be parsed.
     * 
     * @LWR 1.g.g.d. The command must output an error with any requirements 
     * that cannot be parsed.
     * 
     * @LWR 1.g.g.e. The command must output an error with any requirement 
     * immediately following a gap.
     *
     * @LWR 1.g.g.f. The command must output an error with any requirement 
     * with an ID that is duplicated.
     *
     * @LWR 1.g.g.h. The command must output a flag with any requirements that 
     * are not addressed.
     *
     * @LWR 1.g.g.g. The command must output an error with any requirement 
     * that is out of order.
     *
     * @LWR 1.g.l. The command should output the number of duplicate ID's.
     */
    public function testReportAllTheThings()
    {
        $analysis = new Analysis();
        $analysis->progress = 1;
        $analysis->active = 2;
        $analysis->addressed = 4;
        $analysis->obsolete = 8;
        $analysis->requirements = [
            new RequirementAnalysis([
                'line'            => 'Wooo',
                'is_inactive'     => true,
                'has_error'       => true,
                'is_obsolete'     => true,
                'is_incomplete'   => true,
                'has_warning'     => true,
                'is_pending'      => true,
                'has_parse_error' => true,
                'notes'           => ['We'],
            ]),
        ];
        $analysis->duplicateIdErrorCount = 5;

        $reporter = new Reporter($analysis, Reporter::VERY_VERBOSE);

        $command = Mockery::mock(Command::class);
        $command->shouldReceive('line')
            ->with('Use -v or -vv to see more information.');
        $command->shouldReceive('info')
            ->with('Progress: 1%')
            ->once();
        $command->shouldReceive('info')
            ->with('Requirements: 2')
            ->once();
        $command->shouldReceive('info')
            ->with('Addressed: 4')
            ->once();
        $command->shouldReceive('info')
            ->with('Obsolete: 8')
            ->once();

        $command->shouldReceive('error')
            ->with('5 requirements use duplicate IDs.')
            ->once();

        $command->shouldReceive('table')
            ->with(
                ['State', 'Requirement'],
                [
                    ['X-?!I', "Wooo\nWe"],
                ]
            )
            ->once();

        $reporter->report($command);
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
     * @LWR 1.g.f.a In normal mode and above the command must output any 
     * requirements that are not addressed and not obsolete as well as any 
     * incomplete requirements that are addressed and not obsolete.
     *
     * @LWR 1.g.g.h. The command must output a flag with any requirements that 
     * are not addressed.
     *
     * @LWR 1.g.g.c. The command must output a warning with any requirements 
     * that are incomplete.
     *
     * @LWR 1.g.g.a. The command must output a warning with any requirements 
     * that do not use the keywords defined in RFC 2119.
     *
     * @LWR 1.g.h. The command should output the number of warnings due to 
     * missing RFC 2119 keywords.
     * 
     * @LWR 1.g.i. The command should output the number of warnings due to 
     * unexpected custom flags.
     *
     * @LWR 1.g.j. The command should output the number of errors due to 
     * failure to parse.
     *
     * @LWR 1.g.k. The command should output the number of errors due to gaps.
     *
     * @LWR 1.g.g.d.a. The command MUST output parse errors even for 
     * requirements that do not fall within the super ID supplied to the 
     * command.
     */
    public function testReportFewestThings_Normal()
    {
        $analysis = new Analysis();
        $analysis->progress = 60;
        $analysis->active = 5;
        $analysis->addressed = 3;
        $analysis->obsolete = 1;
        $analysis->requirements = [
            new RequirementAnalysis([
                'line' => '1. (X) Must love dogs',
                'is_obsolete' => true,
            ]),
            new RequirementAnalysis([
                'line' => '2. (I) John Tucker must die',
                'is_incomplete' => true,
            ]),
            new RequirementAnalysis([
                'line' => '  2.a. This must be the place',
            ]),

            new RequirementAnalysis([]),

            new RequirementAnalysis([
                'line' => '3. The gods must be crazy',
            ]),
            new RequirementAnalysis([
                'line' => '  3.a. Funny you should ask',
                'is_pending' => true,
            ]),  
            new RequirementAnalysis([
                'line' => '  3.b. It shood happen to you',
                'is_pending' => true,
                'has_warning' => true,
                'notes' => ['Well-written requirements use RFC 2119 keywords such as MUST, SHOULD, and MAY'],
            ]),

            new RequirementAnalysis([]),

            new RequirementAnalysis([
                'line'            => 'PARSE ERROR',
                'has_parse_error' => true,
            ]),
        ];
        $analysis->rfc2119WarningCount = 1;
        $analysis->customFlagWarningCount = 2;
        $analysis->parseFailureCount = 3;
        $analysis->gapErrorCount = 4;

        $reporter = new Reporter($analysis, Reporter::NORMAL);

        $command = Mockery::mock(Command::class);
        $command->shouldReceive('line')
            ->with('Use -v or -vv to see more information.');
        $command->shouldReceive('info')
            ->with('Progress: 60%')
            ->once();
        $command->shouldReceive('info')
            ->with('Requirements: 5')
            ->once();
        $command->shouldReceive('info')
            ->with('Addressed: 3')
            ->once();
        $command->shouldReceive('info')
            ->with('Obsolete: 1')
            ->once();

        $command->shouldReceive('comment')
            ->with('1 requirement uses weak language.')
            ->once();
        $command->shouldReceive('comment')
            ->with('2 requirements use bad flags.')
            ->once();
        $command->shouldReceive('error')
            ->with('3 requirements cannot be parsed.')
            ->once();
        $command->shouldReceive('error')
            ->with('4 requirements are out of order.')
            ->once();

        $command->shouldReceive('table')
            ->with(
                ['State', 'Requirement'],
                [
                    ['I', '2. (I) John Tucker must die'],
                    ['', ''],
                    ['-', '  3.a. Funny you should ask'],
                    ['-?', "  3.b. It shood happen to you\nWell-written requirements use RFC 2119 keywords such as MUST, SHOULD, and MAY"],
                    ['', ''],
                    ['', 'PARSE ERROR'],
                ]
            )
            ->once();

        $reporter->report($command);
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
     * @LWR 1.g.f.b. In verbose mode and above the command must output all 
     * requirements that are not obsolete.
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
     * @LWR 1.g.h. The command should output the number of warnings due to 
     * missing RFC 2119 keywords.
     * 
     * @LWR 1.g.i. The command should output the number of warnings due to 
     * unexpected custom flags.
     *
     * @LWR 1.g.j. The command should output the number of errors due to 
     * failure to parse.
     *
     * @LWR 1.g.k. The command should output the number of errors due to gaps.
     *
     * @LWR 1.g.g.d.a. The command MUST output parse errors even for 
     * requirements that do not fall within the super ID supplied to the 
     * command.
     */
    public function testReportFewerThings_Verbose()
    {
        $analysis = new Analysis();
        $analysis->progress = 60;
        $analysis->active = 5;
        $analysis->addressed = 3;
        $analysis->obsolete = 1;
        $analysis->requirements = [
            new RequirementAnalysis([
                'line' => '1. (X) Must love dogs',
                'is_obsolete' => true,
            ]),
            new RequirementAnalysis([
                'line' => '2. (I) John Tucker must die',
                'is_incomplete' => true,
            ]),
            new RequirementAnalysis([
                'line' => '  2.a. This must be the place',
            ]),

            new RequirementAnalysis([]),

            new RequirementAnalysis([
                'line' => '3. The gods must be crazy',
            ]),
            new RequirementAnalysis([
                'line' => '  3.a. Funny you should ask',
                'is_pending' => true,
            ]),  
            new RequirementAnalysis([
                'line' => '  3.b. It shood happen to you',
                'is_pending' => true,
                'has_warning' => true,
                'notes' => ['Well-written requirements use RFC 2119 keywords such as MUST, SHOULD, and MAY'],
            ]),

            new RequirementAnalysis([]),

            new RequirementAnalysis([
                'line'            => 'PARSE ERROR',
                'has_parse_error' => true,
            ]),
        ];
        $analysis->rfc2119WarningCount = 1;
        $analysis->customFlagWarningCount = 2;
        $analysis->parseFailureCount = 3;
        $analysis->gapErrorCount = 4;

        $reporter = new Reporter($analysis, Reporter::VERBOSE);

        $command = Mockery::mock(Command::class);
        $command->shouldReceive('line')
            ->with('Use -v or -vv to see more information.');
        $command->shouldReceive('info')
            ->with('Progress: 60%')
            ->once();
        $command->shouldReceive('info')
            ->with('Requirements: 5')
            ->once();
        $command->shouldReceive('info')
            ->with('Addressed: 3')
            ->once();
        $command->shouldReceive('info')
            ->with('Obsolete: 1')
            ->once();

        $command->shouldReceive('comment')
            ->with('1 requirement uses weak language.')
            ->once();
        $command->shouldReceive('comment')
            ->with('2 requirements use bad flags.')
            ->once();
        $command->shouldReceive('error')
            ->with('3 requirements cannot be parsed.')
            ->once();
        $command->shouldReceive('error')
            ->with('4 requirements are out of order.')
            ->once();

        $command->shouldReceive('table')
            ->with(
                ['State', 'Requirement'],
                [
                    ['I', '2. (I) John Tucker must die'],
                    ['', '  2.a. This must be the place'],
                    ['', ''],
                    ['', '3. The gods must be crazy'],
                    ['-', '  3.a. Funny you should ask'],
                    ['-?', "  3.b. It shood happen to you\nWell-written requirements use RFC 2119 keywords such as MUST, SHOULD, and MAY"],
                    ['', ''],
                    ['', 'PARSE ERROR'],
                ]
            )
            ->once();

        $reporter->report($command);
    }

    /**
     * @LWR 1.g.f.d. Repeated blank lines should be collapsed to one.
     *
     * @LWR 1.g.f.e. Leading and trailing blank lines should not be shown in 
     * the report.
     */
    public function testReportSingleBlanks()
    {
        $analysis = new Analysis();
        $analysis->requirements = [
            new RequirementAnalysis([
                'line' => '1. (X) Must love dogs',
                'is_obsolete' => true,
            ]),
            new RequirementAnalysis([]),
            new RequirementAnalysis([
                'line' => '2. (I) John Tucker must die',
                'is_incomplete' => true,
            ]),
            new RequirementAnalysis([]),
            new RequirementAnalysis([
                'line' => '  2.a. This must be the place',
            ]),

            new RequirementAnalysis([
                'line' => '3. The gods must be crazy',
            ]),
            new RequirementAnalysis([]),
            new RequirementAnalysis([
                'line' => '  3.a. Funny you should ask',
                'is_pending' => true,
            ]),  
            new RequirementAnalysis([]),
            new RequirementAnalysis([]),
            new RequirementAnalysis([
                'line' => '  3.b. It shood happen to you',
                'is_pending' => true,
                'has_warning' => true,
                'notes' => ['Well-written requirements use RFC 2119 keywords such as MUST, SHOULD, and MAY'],
            ]),
            new RequirementAnalysis([]),
        ];

        $reporter = new Reporter($analysis, Reporter::NORMAL);

        $command = Mockery::mock(Command::class);
        $command->shouldReceive('line');
        $command->shouldReceive('info');
        $command->shouldReceive('comment');
        $command->shouldReceive('error');

        $command->shouldReceive('table')
            ->with(
                ['State', 'Requirement'],
                [
                    ['I', '2. (I) John Tucker must die'],
                    ['', ''],
                    ['-', '  3.a. Funny you should ask'],
                    ['', ''],
                    ['-?', "  3.b. It shood happen to you\nWell-written requirements use RFC 2119 keywords such as MUST, SHOULD, and MAY"],
                ]
            )
            ->once();

        $reporter->report($command);
    }
}
