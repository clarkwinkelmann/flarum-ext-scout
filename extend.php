<?php

namespace ClarkWinkelmann\Scout;

use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Extend;
use Flarum\User\Search\UserSearcher;
use Laravel\Scout\Console as ScoutConsole;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js'),

    new Extend\Locales(__DIR__ . '/resources/locale'),

    (new Extend\ServiceProvider())
        ->register(ScoutServiceProvider::class),

    (new Extend\SimpleFlarumSearch(DiscussionSearcher::class))
        ->setFullTextGambit(Search\DiscussionGambit::class),
    (new Extend\SimpleFlarumSearch(UserSearcher::class))
        ->setFullTextGambit(Search\UserGambit::class),

    (new Extend\Console())
        ->command(Console\ImportAll::class)
        ->command(ScoutConsole\FlushCommand::class)
        ->command(ScoutConsole\ImportCommand::class)
        ->command(ScoutConsole\IndexCommand::class)
        ->command(ScoutConsole\DeleteIndexCommand::class),
];
