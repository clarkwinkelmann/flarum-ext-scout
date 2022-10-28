<?php

namespace ClarkWinkelmann\Scout\Console;

use Illuminate\Contracts\Events\Dispatcher;

class ImportCommand extends \Laravel\Scout\Console\ImportCommand
{
    use ModifiedImportTrait;

    public function handle(Dispatcher $events)
    {
        $class = $this->argument('model');

        $this->handleClass($events, $class);
    }
}
