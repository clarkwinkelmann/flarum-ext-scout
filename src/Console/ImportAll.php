<?php

namespace ClarkWinkelmann\Scout\Console;

use ClarkWinkelmann\Scout\Model;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Scout\Events\ModelsImported;

class ImportAll extends Command
{
    protected $signature = 'scout:import-all
            {--c|chunk= : The number of records to import at a time}';

    protected $description = 'Import all Flarum models into the search index';

    public function handle(Dispatcher $events)
    {
        // Code based on Laravel\Scout\Console\ImportCommand
        foreach ([
                     Model\Discussion::class => 'discussion',
                     Model\Post::class => 'post',
                     Model\User::class => 'user',
                 ] as $class => $label) {

            $model = new $class;

            $events->listen(ModelsImported::class, function ($event) use ($label) {
                $key = $event->models->last()->getScoutKey();

                $this->line('<comment>Imported ' . $label . ' models up to ID:</comment> ' . $key);
            });

            $model::makeAllSearchable($this->option('chunk'));

            $events->forget(ModelsImported::class);

            $this->info('All ' . $label . ' records have been imported.');
        }
    }
}
