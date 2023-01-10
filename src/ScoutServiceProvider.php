<?php

namespace ClarkWinkelmann\Scout;

use ClarkWinkelmann\Scout\Job\MakeSearchable;
use ClarkWinkelmann\Scout\Job\RemoveFromSearch;
use ClarkWinkelmann\Scout\Search\ImprovedGambitManager;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Frontend\Assets;
use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Search\GambitManager;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
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

        // It's better to inject some javascript directly rather than write a javascript module. Benefits:
        // - No additional bundle size if the feature is not used.
        // - No need to wait for app.forum to be ready.
        // - No Webpack overhead code.
        // We could also skip the initializer entirely but this will produce better errors if something goes wrong.
        // Only downside of this approach is that the cache must be cleared when the setting is changed.
        $this->container->resolving('flarum.assets.forum', function (Assets $assets) {
            /**
             * @var $settings SettingsRepositoryInterface
             */
            $settings = $this->container->make(SettingsRepositoryInterface::class);

            $length = (int)$settings->get('clarkwinkelmann-scout.queryMinLength');

            if ($length > 0) {
                $assets->js(function (SourceCollector $sources) use ($length) {
                    $sources->addString(function () use ($length) {
                        return "app.initializers.add('scout-min-length',function(){flarum.core.compat['components/Search'].MIN_SEARCH_LEN=$length});";
                    });
                });
            }
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

        // Override the GambitManager binding set by Flarum's SearchServiceProvider
        $fullTextGambits = $this->container->make('flarum.simple_search.fulltext_gambits');

        foreach ($fullTextGambits as $searcher => $fullTextGambitClass) {
            $this->container
                ->when($searcher)
                ->needs(GambitManager::class)
                ->give(function () use ($searcher, $fullTextGambitClass) {
                    $gambitManager = new ImprovedGambitManager($this->container->make($fullTextGambitClass));
                    foreach (Arr::get($this->container->make('flarum.simple_search.gambits'), $searcher, []) as $gambit) {
                        $gambitManager->add($this->container->make($gambit));
                    }

                    return $gambitManager;
                });
        }
    }
}
