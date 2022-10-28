<?php

namespace ClarkWinkelmann\Scout;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Laravel\Scout\Builder;
use Laravel\Scout\Searchable;

class ScoutModelWrapper extends Model
{
    use Searchable;

    protected $table = 'scout_should_not_be_used';

    protected $realModel;

    public function __construct(Model $realModel)
    {
        parent::__construct([]);

        $this->realModel = $realModel;
    }

    public function getRealModel(): Model
    {
        return $this->realModel;
    }

    /**
     * We could do without this method, but there are instances where we copy-pasted the original Scout code which calls
     * newQuery() directly on the scout model, so we'll proxy it to the real underlying model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQuery()
    {
        return $this->realModel->newQuery();
    }

    /**
     * Combined with the code in SerializesAndRestoresWrappedModelIdentifiers this allows serializing our special wrapped model
     * With only a minimal number of classes overrides (this method is called in Laravel's collection which would be trickier to override)
     * @return mixed
     */
    public function getQueueableId()
    {
        return $this->realModel->getQueueableId();
    }

    public static function bootSearchable()
    {
        // Override original to remove scope (we call it on the real model),
        // observer (we use events and we are not saving the wrapper model anyway)
        // and collection macros (we do it in the service provider)
    }

    public function registerSearchableMacros()
    {
        throw new \Exception('Flarum implementation does not use Searchable::registerSearchableMacros');
    }

    public function queueMakeSearchable($models)
    {
        throw new \Exception('Flarum implementation does not use Searchable::queueMakeSearchable');
    }

    public function queueRemoveFromSearch($models)
    {
        throw new \Exception('Flarum implementation does not use Searchable::queueRemoveFromSearch');
    }

    public function shouldBeSearchable(): bool
    {
        $callbacks = resolve('scout.searchable');

        foreach (array_reverse(array_merge([get_class($this->realModel)], class_parents($this->realModel))) as $class) {
            if (Arr::exists($callbacks, $class)) {
                foreach ($callbacks[$class] as $callback) {
                    $returnValue = $callback($this->realModel);

                    if (is_bool($returnValue)) {
                        return $returnValue;
                    }
                }
            }
        }

        return true;
    }

    public function searchIndexShouldBeUpdated(): bool
    {
        return true;
    }

    public static function search($query = '', $callback = null)
    {
        throw new \Exception('Static functions not available in Scout for Flarum');
    }

    public static function makeAllSearchable($chunk = null)
    {
        throw new \Exception('Static functions not available in Scout for Flarum. Use ScoutStatic::makeAllSearchable');
    }

    public static function removeAllFromSearch()
    {
        throw new \Exception('Static functions not available in Scout for Flarum. Use ScoutStatic::removeAllFromSearch');
    }

    public function queryScoutModelsByIds(Builder $builder, array $ids)
    {
        $query = $this->realModel->newQuery();

        if ($builder->queryCallback) {
            call_user_func($builder->queryCallback, $query);
        }

        $whereIn = in_array($this->getKeyType(), ['int', 'integer']) ?
            'whereIntegerInRaw' :
            'whereIn';

        return $query->{$whereIn}(
            $this->getScoutKeyName(), $ids
        );
    }

    public static function enableSearchSyncing()
    {
        throw new \Exception('Static functions not available in Scout for Flarum');
    }

    public static function disableSearchSyncing()
    {
        throw new \Exception('Static functions not available in Scout for Flarum');
    }

    public static function withoutSyncingToSearch($callback)
    {
        throw new \Exception('Static functions not available in Scout for Flarum');
    }

    public function searchableAs(): string
    {
        /**
         * @var SettingsRepositoryInterface $settings
         */
        $settings = resolve(SettingsRepositoryInterface::class);

        return $settings->get('clarkwinkelmann-scout.prefix') . $this->realModel->getTable();
    }

    public function toSearchableArray(): array
    {
        $callbacks = resolve('scout.attributes');

        $attributes = [];

        foreach (array_reverse(array_merge([get_class($this->realModel)], class_parents($this->realModel))) as $class) {
            if (Arr::exists($callbacks, $class)) {
                foreach ($callbacks[$class] as $callback) {
                    $attributes = array_merge(
                        $attributes,
                        $callback($this->realModel, $attributes)
                    );
                }
            }
        }

        return $attributes;
    }

    public function syncWithSearchUsing()
    {
        // TODO Flarum settings
        return config('scout.queue.connection') ?: config('queue.default');
    }

    public function syncWithSearchUsingQueue()
    {
        // TODO Flarum settings
        return config('scout.queue.queue');
    }

    public function pushSoftDeleteMetadata()
    {
        throw new \Exception('Native Laravel soft delete meta not implemented in Scout for Flarum');
    }

    public function getScoutKey()
    {
        return $this->realModel->getKey();
    }

    public function getScoutKeyName()
    {
        return $this->realModel->getQualifiedKeyName();
    }

    protected static function usesSoftDelete()
    {
        throw new \Exception('Native Laravel soft delete meta not implemented in Scout for Flarum');
    }

    /**
     * Replaces the ModelObserver's callback, to be called by event listeners in Flarum instead
     */
    public function scoutObserverSaved()
    {
        if (!$this->searchIndexShouldBeUpdated()) {
            return;
        }

        if (!$this->shouldBeSearchable()) {
            if ($this->wasSearchableBeforeUpdate()) {
                $this->unsearchable();
            }

            return;
        }

        $this->searchable();
    }

    /**
     * Replaces the ModelObserver's callback, to be called by event listeners in Flarum instead
     */
    public function scoutObserverDeleted()
    {
        if (!$this->wasSearchableBeforeDelete()) {
            return;
        }

        $this->unsearchable();
    }
}
