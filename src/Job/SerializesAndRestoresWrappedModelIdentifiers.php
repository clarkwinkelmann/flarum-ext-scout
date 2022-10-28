<?php

namespace ClarkWinkelmann\Scout\Job;

use ClarkWinkelmann\Scout\ScoutModelWrapper;
use Illuminate\Contracts\Database\ModelIdentifier;
use Illuminate\Contracts\Queue\QueueableCollection;

/**
 * Overrides the right methods to allow passing a collection of wrapped models to a queued job
 */
trait SerializesAndRestoresWrappedModelIdentifiers
{
    protected function getSerializedPropertyValue($value)
    {
        if ($value instanceof QueueableCollection) {
            $first = $value->first();

            if ($first instanceof ScoutModelWrapper) {
                return new ModelIdentifier(
                // This is the reason we need to extend this whole method, because the class is otherwise not accessible
                // We could also extend the RemoveableScoutCollection that would only help for one of the Scout jobs
                    get_class($first->getRealModel()),
                    // This can be left as-is, we modified the method called in ScoutModelWrapper
                    $value->getQueueableIds(),
                    $value->getQueueableRelations(),
                    $value->getQueueableConnection()
                );
            }
        }

        return parent::getRestoredPropertyValue($value);
    }

    protected function restoreCollection($value)
    {
        return parent::restoreCollection($value)->map(function ($model) {
            // The class stored is the real class, now we wrap it again so we don't have to modify every method in this job
            return new ScoutModelWrapper($model);
        });
    }
}
