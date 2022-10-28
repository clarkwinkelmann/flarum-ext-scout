<?php

namespace ClarkWinkelmann\Scout\Job;

use ClarkWinkelmann\Scout\ScoutModelWrapper;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class RemoveFromSearch extends \Laravel\Scout\Jobs\RemoveFromSearch
{
    use SerializesAndRestoresWrappedModelIdentifiers;

    protected function restoreCollection($value)
    {
        if (!$value->class || count($value->id) === 0) {
            return new EloquentCollection;
        }

        return new EloquentCollection(
            collect($value->id)->map(function ($id) use ($value) {
                $model = new ScoutModelWrapper(new $value->class);

                $keyName = $this->getUnqualifiedScoutKeyName(
                    $model->getScoutKeyName()
                );

                $model->getRealModel()->forceFill([$keyName => $id]);

                return $model;
            })
        );
    }
}
