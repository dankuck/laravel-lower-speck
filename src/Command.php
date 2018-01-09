<?php

namespace LowerSpeck;

class Command extends \Illuminate\Console\Command
{

    protected $signature = 'requirements:check {id?}';

    protected $description = 'Check the Lower Speck requirements';

    public function handle()
    {
        $id = $this->argument('id');
        if ($id && $id[strlen($id) - 1] != '.') {
            $id .= '.';
        }

        $this->info('Loading config...', 'v');

        $config = app(Config::class, [base_path('lower-speck.json')]);

        $this->info('Parsing requirements.lwr...', 'v');

        $specification = app(Parser::class, [base_path('requirements.lwr')])->getSpecification();

        $this->info('Grepping code base...', 'v');

        $paths = array_map('base_path', $config->paths());

        $grepper = app(ReferenceGrepper::class, [$paths]);

        $this->info('Processing...', 'v');

        $analysis = app(Analyzer::class, [$specification, $grepper])->getAnalysis($id);

        $this->info('Results:');

        app(Reporter::class, [$analysis, $this->getVerbosity(), $id])->report($this);
    }

    private function getVerbosity() : int
    {
        if ($this->output->isVeryVerbose()) {
            return Reporter::VERY_VERBOSE;
        }
        if ($this->output->isVerbose()) {
            return Reporter::VERBOSE;
        }
        return Reporter::NORMAL;
    }
}
