<?php

namespace ClarkWinkelmann\Scout\Model;

use Flarum\Database\AbstractModel;
use Flarum\Settings\SettingsRepositoryInterface;
use Laravel\Scout\SearchableScope;

trait CommonModelTrait
{
    public static function bootSearchable()
    {
        // Same as original but without the ModelObserver
        // because we are not going to use it and because it calls a Facade that doesn't exist in Flarum

        static::addGlobalScope(new SearchableScope);

        (new static)->registerSearchableMacros();
    }

    public function searchableAs()
    {
        /**
         * @var SettingsRepositoryInterface $settings
         */
        $settings = resolve(SettingsRepositoryInterface::class);

        return $settings->get('clarkwinkelmann-scout.prefix') . $this->getTable();
    }

    public function cloneFlarumModel(AbstractModel $original): AbstractModel
    {
        $instance = $this->newInstance([], true);
        $instance->attributes = $original->attributes;
        $instance->original = $original->original;
        $instance->changes = $original->changes;

        return $instance;
    }
}
