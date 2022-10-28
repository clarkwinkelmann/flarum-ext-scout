<?php

namespace ClarkWinkelmann\Scout;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Collection;
use Laravel\Scout\Events\ModelsFlushed;
use Laravel\Scout\Events\ModelsImported;

/**
 * A re-implementation of Scout's SearchableScope that we can apply to generic models that don'have the Searchable trait
 */
class FlarumSearchableScope implements Scope
{
    public function apply(EloquentBuilder $builder, Model $model)
    {
        //
    }

    protected static function dispatchEvent(string $eventClass, Collection $models)
    {
        resolve(Dispatcher::class)->dispatch(new $eventClass($models->map(function (Model $model) {
            // For consistency, extract any wrapper so that the event contains an array of real models only
            if ($model instanceof ScoutModelWrapper) {
                return $model->getRealModel();
            }

            return $model;
        })));
    }

    public function extend(EloquentBuilder $builder)
    {
        $builder->macro('searchable', function (EloquentBuilder $builder, $chunk = null) {
            $builder->chunkById($chunk ?: 500, function ($models) {
                $models->filter(function (Model $model) {
                    if (!($model instanceof ScoutModelWrapper)) {
                        $model = new ScoutModelWrapper($model);
                    }

                    return $model->shouldBeSearchable();
                })->searchable();

                FlarumSearchableScope::dispatchEvent(ModelsImported::class, $models);
            });
        });

        $builder->macro('unsearchable', function (EloquentBuilder $builder, $chunk = null) {
            $builder->chunkById($chunk ?: 500, function ($models) {
                $models->unsearchable();

                FlarumSearchableScope::dispatchEvent(ModelsFlushed::class, $models);
            });
        });

        HasManyThrough::macro('searchable', function ($chunk = null) {
            $this->chunkById($chunk ?: 500, function ($models) {
                $models->filter(function (Model $model) {
                    if (!($model instanceof ScoutModelWrapper)) {
                        $model = new ScoutModelWrapper($model);
                    }

                    return $model->shouldBeSearchable();
                })->searchable();

                FlarumSearchableScope::dispatchEvent(ModelsImported::class, $models);
            });
        });

        HasManyThrough::macro('unsearchable', function ($chunk = null) {
            $this->chunkById($chunk ?: 500, function ($models) {
                $models->unsearchable();

                FlarumSearchableScope::dispatchEvent(ModelsFlushed::class, $models);
            });
        });
    }
}
