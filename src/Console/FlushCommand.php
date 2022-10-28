<?php

namespace ClarkWinkelmann\Scout\Console;

use ClarkWinkelmann\Scout\ScoutStatic;

class FlushCommand extends \Laravel\Scout\Console\FlushCommand
{
    public function handle()
    {
        $class = $this->argument('model');

        ScoutStatic::removeAllFromSearch($class);

        $this->info('All [' . $class . '] records have been flushed.');
    }
}
