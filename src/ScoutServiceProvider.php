<?php

namespace ClarkWinkelmann\Scout;

use Flarum\Discussion\Discussion;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Post\CommentPost;
use Flarum\Post\Post;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Laravel\Scout\EngineManager;
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
    }

    public function boot()
    {
        CommentPost::observe(new FlarumModelObserver());
        Discussion::observe(new FlarumModelObserver());
        Post::observe(new FlarumModelObserver());
        User::observe(new FlarumModelObserver());
    }
}
