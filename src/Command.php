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

        $checker = $this->make(Checker::class, ['base_path' => base_path()]);

        $analysis = $checker->check($id);

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
