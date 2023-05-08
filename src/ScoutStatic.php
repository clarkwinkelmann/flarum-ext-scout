<?php

namespace ClarkWinkelmann\Scout;

use ClarkWinkelmann\AdvancedSearchHighlight\Highlighter;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Arr;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\MeiliSearchEngine;

/**
 * All the static method can't be implemented on UniversalModel since we don't know the target model
 * This class re-implements those methods identically, except they take the model class as first parameter
 */
class ScoutStatic
{
    // This is not really the attributes to highlight, rather it's the attributes highlights will be extracted for
    // The actual list of attributes to highlight is configured in the advanced-search-highlight extension
    public static $attributesToHighlight = [
        Discussion::class => [
            'title',
        ],
        Post::class => [
            'content',
        ],
    ];

    protected static function useQueue(): bool
    {
        $settings = resolve(SettingsRepositoryInterface::class);
        return (bool)$settings->get('clarkwinkelmann-scout.queue');
    }

    /**
     * Replacement for Searchable::makeAllSearchable
     * @param string $class
     * @param null $chunk
     */
    public static function makeAllSearchable(string $class, $chunk = null)
    {
        $self = new ScoutModelWrapper(new $class);

        $self->newQuery()
            ->when(true, function ($query) use ($self) {
                // We would need to change the visibility of this method to work here
                // Since we know we are not using it, we will comment it out for now
                //$self->makeAllSearchableUsing($query);
            })
            ->orderBy($self->getKeyName())
            ->searchable($chunk);
    }

    /**
     * Replacement for Searchable::removeAllFromSearch
     * @param string $class
     */
    public static function removeAllFromSearch(string $class)
    {
        $self = new ScoutModelWrapper(new $class);

        $self->searchableUsing()->flush($self);
    }

    /**
     * Shortcut to obtain a Builder instance with the wrapper already set
     * @param string $class
     * @param string $query
     * @param callable? $callback
     * @return Builder
     */
    public static function makeBuilder(string $class, string $query, $callback = null): Builder
    {
        $wrapped = new ScoutModelWrapper(new $class);

        $isMeilisearch = $wrapped->searchableUsing() instanceof MeiliSearchEngine;

        if ($isMeilisearch && is_null($callback) && class_exists(Highlighter::class)) {
            $callback = function ($meilisearch, $query, $searchParams) use ($class) {
                $attributes = Arr::get(self::$attributesToHighlight, $class) ?? [];

                $results = $meilisearch->rawSearch($query, $searchParams + [
                        'attributesToHighlight' => $attributes,
                        'showMatchesPosition' => true,
                    ]);

                foreach ($results['hits'] as $hit) {
                    foreach ($attributes as $attribute) {
                        $positions = Arr::get($hit, '_matchesPosition.' . $attribute);

                        if (is_array($positions)) {
                            foreach ($positions as $position) {
                                $after = substr($hit[$attribute], $position['start'] + $position['length'], 1);

                                Highlighter::addHighlightRule(
                                    substr($hit[$attribute], $position['start'], $position['length']),
                                    $position['start'] === 0 ? null : substr($hit[$attribute], $position['start'] - 1, 1),
                                    $after === '' ? null : $after
                                );
                            }
                        }
                    }
                }

                return $results;
            };
        }

        $builder = resolve(Builder::class, [
            'model' => $wrapped,
            'query' => $query,
            'callback' => $callback,
        ]);

        $settings = resolve(SettingsRepositoryInterface::class);
        $limit = (int)$settings->get('clarkwinkelmann-scout.limit');

        // This becomes the new default for every usage of the Builder class
        // Developers can still customize it after getting the instance
        if ($limit > 0) {
            $builder->take($limit);
        } else if ($isMeilisearch) {
            // Meilisearch default limit of 20 is extremely low
            // If you have a large number of models that aren't visible to guests
            // you might end up with not a single result after the visibility scope is applied
            // This value is only applied if the user didn't customize the setting above
            // This value was not chosen for any particular reason. I expect it to change in the future based on feedback
            $builder->take(200);
        }

        return $builder;
    }
}
