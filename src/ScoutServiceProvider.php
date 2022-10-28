<?php

namespace ClarkWinkelmann\Scout;

use ClarkWinkelmann\Scout\Job\MakeSearchable;
use ClarkWinkelmann\Scout\Job\RemoveFromSearch;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Collection;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Scout;
use MeiliSearch\Client as MeiliSearch;

/**
 * Similar to Laravel\Scout\ScoutServiceProvider without the config and command parts
 */
class ScoutServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        if (class_exists(MeiliSearch::class)) {
            $this->container->singleton(MeiliSearch::class, function () {
                /**
                 * @var SettingsRepositoryInterface $settings
                 */
                $settings = $this->container->make(SettingsRepositoryInterface::class);

                return new MeiliSearch(
                    $settings->get('clarkwinkelmann-scout.meilisearchHost') ?: '127.0.0.1:7700',
                    $settings->get('clarkwinkelmann-scout.meilisearchKey')
                );
            });
        }

        $this->container->singleton(EngineManager::class, function ($app) {
            return new FlarumEngineManager($app);
        });

        Scout::makeSearchableUsing(MakeSearchableDisable::class);
        Scout::removeFromSearchUsing(MakeSearchableDisable::class);

        $this->container->singleton('scout.searchable', function () {
            return [];
        });

        $this->container->singleton('scout.attributes', function () {
            return [];
        });
    }

    public function boot()
    {
        Collection::macro('searchable', function () {
            /**
             * @var Collection $this
             */
            if ($this->isEmpty()) {
                return;
            }

            $wrappedCollection = $this->map(function ($model) {
                if ($model instanceof ScoutModelWrapper) {
                    return $model;
                }

                return new ScoutModelWrapper($model);
            });

            $first = $wrappedCollection->first();

            $settings = resolve(SettingsRepositoryInterface::class);

            if (!$settings->get('clarkwinkelmann-scout.queue')) {
                return $first->searchableUsing()->update($wrappedCollection);
            }

            // Queue and connection choice has been removed compared to original Scout code
            // could be re-introduced later if we implement them in the Flarum version
            resolve(Dispatcher::class)->dispatch(new MakeSearchable($wrappedCollection));
        });

        Collection::macro('unsearchable', function () {
            /**
             * @var Collection $this
             */
            if ($this->isEmpty()) {
                return;
            }

            $wrappedCollection = $this->map(function ($model) {
                if ($model instanceof ScoutModelWrapper) {
                    return $model;
                }

                return new ScoutModelWrapper($model);
            });

            $first = $wrappedCollection->first();

            $settings = resolve(SettingsRepositoryInterface::class);

            if (!$settings->get('clarkwinkelmann-scout.queue')) {
                return $first->searchableUsing()->delete($wrappedCollection);
            }

            // Queue and connection choice has been removed compared to original Scout code
            resolve(Dispatcher::class)->dispatch(new RemoveFromSearch($wrappedCollection));
        });
    }
}
