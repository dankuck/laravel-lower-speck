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

        $config = $this->make(Config::class, ['filepath' => base_path('lower-speck.json')]);

        $this->info('Parsing requirements.lwr...', 'v');

        $specification = $this->make(Parser::class, ['filepath' => base_path('requirements.lwr')])->getSpecification();

        $this->info('Grepping code base...', 'v');

        $paths = array_map('base_path', $config->paths());

        $grepper = $this->make(ReferenceGrepper::class, ['paths' => $paths]);

        $this->info('Processing...', 'v');

        $analysis = $this->make(Analyzer::class, [
                'specification' => $specification,
                'grepper'       => $grepper,
            ])
            ->getAnalysis($id);

        $this->info('Results:');

        $this->make(Reporter::class, [
                'analysis'  => $analysis, 
                'verbosity' => $this->getVerbosity(),
            ])
            ->report($this);
    }

    private function make(string $class, array $params)
    {
        $app = app();
        if (method_exists($app, 'makeWith')) {
            return $app->makeWith($class, $params);
        } else {
            return $app->make($class, $params);
        }
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
