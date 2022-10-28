<?php

namespace ClarkWinkelmann\Scout\Console;

use ClarkWinkelmann\Scout\ScoutModelWrapper;
use ClarkWinkelmann\Scout\ScoutStatic;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Scout\Events\ModelsImported;

trait ModifiedImportTrait
{
    protected function handleClass(Dispatcher $events, string $class)
    {
        $events->listen(ModelsImported::class, function ($event) use ($class) {
            // The models in the event are the real models, not wrapped, so we can't use getScoutKey() directly
            $key = (new ScoutModelWrapper($event->models->last()))->getScoutKey();

            $this->line('<comment>Imported [' . $class . '] models up to ID:</comment> ' . $key);
        });

        // Same as original with this line modified to use ScoutStatic
        ScoutStatic::makeAllSearchable($class, $this->option('chunk'));

        $events->forget(ModelsImported::class);

        $this->info('All [' . $class . '] records have been imported.');
    }
}
