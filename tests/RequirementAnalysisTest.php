<?php

namespace LowerSpeck;

use TestCase;
use Mockery;

class RequirementAnalysisTest extends TestCase
{

    public function testInstantiate()
    {
        $one = new RequirementAnalysis([]);
        $this->assertEquals('', $one->line);
        $this->assertFalse($one->has_error);
        $this->assertFalse($one->is_obsolete);
        $this->assertFalse($one->is_incomplete);
        $this->assertFalse($one->has_warning);
        $this->assertFalse($one->is_pending);
        $this->assertEquals([], $one->notes);

        $two = new RequirementAnalysis([
            'line' => 'ohai',
            'has_error' => true,
            'is_obsolete' => true,
            'is_incomplete' => true,
            'has_warning' => true,
            'is_pending' => true,
            'notes' => ['yeah'],
        ]);
        $this->assertEquals('ohai', $two->line);
        $this->assertTrue($two->has_error);
        $this->assertTrue($two->is_obsolete);
        $this->assertTrue($two->is_incomplete);
        $this->assertTrue($two->has_warning);
        $this->assertTrue($two->is_pending);
        $this->assertEquals(['yeah'], $two->notes);
    }
}
