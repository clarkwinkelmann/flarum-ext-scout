# Scout Search for Flarum

[![MIT license](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/clarkwinkelmann/flarum-ext-scout/blob/master/LICENSE.md) [![Latest Stable Version](https://img.shields.io/packagist/v/clarkwinkelmann/flarum-ext-scout.svg)](https://packagist.org/packages/clarkwinkelmann/flarum-ext-scout) [![Total Downloads](https://img.shields.io/packagist/dt/clarkwinkelmann/flarum-ext-scout.svg)](https://packagist.org/packages/clarkwinkelmann/flarum-ext-scout) [![Donate](https://img.shields.io/badge/paypal-donate-yellow.svg)](https://www.paypal.me/clarkwinkelmann)

Integrates [Laravel Scout](https://laravel.com/docs/9.x/scout) with [Flarum](https://flarum.org/) discussion and user search.

Just like with Laravel, the data is automatically synced with the search index every time a model is updated in Flarum.
You only need to manually import data when you enable the extension (see commands below).

The external search driver is used server-side to filter down the MySQL results, so it should still be compatible with every other extension and search gambits.

[Algolia](https://www.algolia.com/) and [Meilisearch](https://www.meilisearch.com/) drivers are included in the extension.
[TNTSearch](https://github.com/teamtnt/tntsearch) is supported but requires the manual installation of an additional package.
The Scout database and collection drivers cannot be used (they would be worst than Flarum's built-in database search).

See below for the specific requirements and configuration of each driver.

While only discussions and users are searchable in Flarum, this implementation also uses a `posts` search index which is merged with discussion search results in a similar way to the Flarum native search.
The discussion result sort currently prioritize best post matching because I have not found a way to merge the match score of discussions and posts indices.

All CLI commands from Scout are available, with an additional special "import all" command:

```
php flarum scout:import-all           Import all Flarum models into the search index
                                      (a shortcut to scout:import with every searchable class known to Flarum)
php flarum scout:flush {model}        Flush all of the model's records from the index
php flarum scout:import {model}       Import the given model into the search index
php flarum scout:index {name}         Create an index (generally not needed)
php flarum scout:delete-index {name}  Delete an index (generally not needed)
```

### Algolia

The Algolia driver requires an account on the eponymous cloud service.

### Meilisearch

The Meilisearch driver requires a running Meilisearch server instance.
The server can be hosted anywhere as long as it can be reached over the network.
By default, the extension attempts to connect to a server at `127.0.0.1:7700`.

If you are not running the latest Meilisearch version you might need to explicitly install an older version of the SDK.
Likewise, if you are regularly running `composer update` on all your dependencies, you should also add an explicit requirement for the Meilisearch SDK in your `composer.json` because the extension requires `*` which might jump to a newer Meilisearch SDK version as soon as it comes out.

To install and lock the current latest version:

    composer require meilisearch/meilisearch-php

Unfortunately Meilisearch doesn't seem to advertise which specific version of the Composer package is compatible with each server version.
You can find the list of releases at https://packagist.org/packages/meilisearch/meilisearch-php

Once you know which version you need, you can lock it, for example to install the older 0.23:

    composer require meilisearch/meilisearch-php:"0.23.*"

The only settings for Meilisearch are **Host** and **Key**.
Everything else is configured in the Meilisearch server itself.

Even if you don't configure the **Default Results Limit** value and use Meilisearch, the extension will automatically set it to 200 for you because the default for Meilisearch (20) is extremely low and result in only 2 pages of results at best.

### TNTSearch

The TNTSearch library requires the sqlite PHP extension, therefore it's not included by default with Scout.

To install it, make sure you have the sqlite PHP extension enabled for both command line and webserver and run:

    composer require teamtnt/laravel-scout-tntsearch-driver

TNTSearch uses local sqlite databases for each index.
The databases are stored in `<flarum>/storage/tntsearch` which must be writable.

The following settings are exposed.
What each setting does isn't entirely clear, TNTSearch own documentation doesn't offer much guidance.

- **Max Docs**: this likely impacts how many results Flarum will be able to show for a query
- **Fuziness** (on/off): seems to be the typos/variation matching
- **Fuzziness Levenshtein Distance**
- **Fuzziness Prefix Length**: no idea what it does
- **Fuzziness Max Expansions** no idea what it does

**As You Type** and **Search Boolean** are hard-coded to enabled, though they don't seem to work as described in TNTSearch documentation.

## Installation

> This extension is still experimental. Please test on a staging server first.
> I have not tested Algolia or async queues yet.

    composer require clarkwinkelmann/flarum-ext-scout

## Supported extensions and fields

This list is not exhaustive.
If you added support for Scout in your extension, let me know so I can update this list.

### Discussions

When searching for discussions via Flarum's search feature, the fields for **Posts** are also queried.

- **Title**: support built into Scout.
- **Formulaire Discussion Fields**: supported since Formulaire 1.8 (guest-accessible forms only).

### Posts

- **Content**: support built into Scout, the value used is a plain text version of the output HTML without any tag. Some information like link URLs, image URLs and image alts are therefore not indexed. This might be changed in a future version.

### Users

- **Display Name**: support built into Scout.
- **Username**: support built into Scout.
- **FoF Bio**: support built into Scout (not in FoF Bio itself).
- **Formulaire Profile Fields**: supported since Formulaire 1.8 (guest-accessible forms only).

Email is intentionally not searchable because there's currently no mechanism that would prevent regular users from using that feature to leak email.

### Formulaire

Forms and Submissions are optionally indexed via Scout. See [Formulaire documentation](https://kilowhat.net/flarum/extensions/formulaire#scout-integration) for details.

## Developers

### Extend the search index of existing models

Use the extender to register your attributes, similar to extending Flarum's serializers.

Additionally, you should register an event listener that's triggered when your attribute value changes.

```php
<?php

use ClarkWinkelmann\Scout\Extend\Scout;
use Acme\Event\SubtitleRenamed;

return [
    (new Scout(Discussion::class))
        ->listenSaved(SubtitleRenamed::class, function (SubtitleRenamed $event) {
            return $event->discussion;
        })
        ->attributes(function (Discussion $discussion): array {
            return [
                'subtitle' => $discussion->subtitle,
            ];
        }),
];
```

If registering an event listener is not an option, you can also call the update code manually after you change the value:

```php
<?php

use ClarkWinkelmann\Scout\ScoutModelWrapper;
use Flarum\Discussion\Discussion;

/**
 * @var Discussion $discussion
 */
$discussion->subtitle = 'New value';
$discussion->save();

(new ScoutModelWrapper($discussion))->scoutObserverSaved();
```

If you are modifying a Flarum model outside the original store/edit/delete handlers, don't forget to trigger Flarum events (like `Started` and `Deleted` for discussions) so that Scout can sync your changes.

### Add your own search engine

Any search engine extending `Laravel\Scout\Engines\Engine` that works with Laravel Scout should be compatible with this Flarum implementation.

There's currently no extender to connect a new engine from an external package.
You will likely need to override the `EngineManager` by forking this extension or by using a container binding.

### Make your own models searchable

Due to the constraints of making Scout optional and extendable, the Scout API for model configuration and retrieval contains a number of changes compared to Laravel.
Where possible, similar names have been kept for the concepts, even if they now happen through extenders or new global methods.

Because there's no way to add the `Searchable` trait to Flarum (or extensions) Eloquent models without making Scout a requirement, the trait is not used.
Do not add the `Searchable` trait to your Eloquent models, even if you have the ability to edit the model source code!

This documentation mentions 2 kind of models, "real" models are the Eloquent models from Flarum or extensions that don't have the `Searchable` trait, like `Flarum\User\User`.
"wrapped" models is a special feature of this extension where a "real" model is wrapped into a special model that gives it the `Searchable` abilities.
Generally, "wrapped" models will be transparently used under the hood by this extension without any special action required by the programmers.
If you wish to manually obtain a "wrapped" model to call specific Scout methods on it, you can wrap it with `new ScoutModelWrapper($model)`.

The built-in Scout model observer is not used, instead Flarum events are used to trigger index updates.

Summary of the differences with Laravel:

The Support/Eloquent collection methods work with arrays of either real or wrapped models:

- `Illuminate\Support\Collection::searchable()`: works identically.
- `Illuminate\Support\Collection::unsearchable()`: works identically.

The query builder methods/scopes work on real models:

- `Eloquent\Builder::searchable()`: works identically
- `Eloquent\Builder::unsearchable()`: works identically

The scout methods aren't available on real models but all the useful methods have alternative means to be called:

- `Model::shouldBeSearchable()`: Use Extender to modify.
- `Model::searchIndexShouldBeUpdated()`: Not customizable. Could be added to Extender later.
- `Model::search()`: Not available. Use Builder directly.
- `Model::makeAllSearchable()`: Use `ScoutStatic::makeAllSearchable()` instead.
- `Model::makeAllSearchableUsing()`: Not customizable. Could be added to Extender later.
- `Model::searchable()`: Not available. Manually wrap in collection or `ScoutModelWrapper` to call.
- `Model::removeAllFromSearch()`: Use `ScoutStatic::removeAllFromSearch()` instead.
- `Model::unsearchable()`: Not available. Manually wrap in collection or `ScoutModelWrapper` to call.
- `Model::wasSearchableBeforeUpdate()`: Not customizable. Could be added to Extender later.
- `Model::wasSearchableBeforeDelete()`: Not customizable. Could be added to Extender later.
- `Model::getScoutModelsByIds()`: Should be usable via wrapper, but I recommend not using it.
- `Model::queryScoutModelsByIds()`: Should be usable via wrapper, but I recommend not using it.
- `Model::enableSearchSyncing()`: Not available.
- `Model::disableSearchSyncing()`: Not available.
- `Model::withoutSyncingToSearch()`: Not available.
- `Model::searchableAs()`: Not customizable. Prefix can be changed in extension settings.
- `Model::toSearchableArray()`: Use Extender to modify.
- `Model::syncWithSearchUsing()`: Not customizable. Could be added to Extender later.
- `Model::syncWithSearchUsingQueue()`: Not customizable. Could be added to Extender later.
- `Model::pushSoftDeleteMetadata()`: Not available.
- `Model::scoutMetadata()`: Not customizable. Could be added to Extender later.
- `Model::withScoutMetadata()`: Not available.
- `Model::getScoutKey()`: Not customizable. Could be added to Extender later.
- `Model::getScoutKeyName()`: Not customizable. Could be added to Extender later.
- `Model::usesSoftDelete()`: Not available.

The `Scout::` static object is not used:

- `Laravel\Scout\Scout::$makeSearchableJob`: Not customizable.
- `Laravel\Scout\Scout::$removeFromSearchJob`: Not customizable.
- `Laravel\Scout\Scout::makeSearchableUsing()`: Not customizable.
- `Laravel\Scout\Scout::removeFromSearchUsing()`: Not customizable.

A new object not part of the original Scout is offered for static methods:

- `ScoutStatic::makeAllSearchable(string $modelClass)`: trigger indexing or every model of the given class.
- `ScoutStatic::removeAllFromSearch(string $modelClass)`: trigger de-indexing or every model of the given class.
- `ScoutStatic::makeBuilder(string $modelClass, string $query, callable $callback = null)`: Obtain an instance of `Laravel\Scout\Builder` configured for a given model.

To use the scout to filter results in your code, I recommend ignoring every builder/model methods and directly retrieve the matching IDs through the Scout Builder instance.

You can then use that array of matching IDs to modify a Flarum searcher (see this extension source code for the post/user searchers), filterer, or a manual query.

Caution: the array of IDs will contain deleted and private content as well.
Make sure to always use Flarum's `whereVisibleTo()` somewhere in the query.

To preserve the search result order, one option is to use the `FIELD()` SQL method.
You could also re-sort the results in PHP after retrieving them from the database if you are not paginating.

```php
<?php

use ClarkWinkelmann\Scout\ScoutStatic;
use Flarum\User\User;

$builder = ScoutStatic::makeBuilder(User::class, 'Hello World');

$ids = $builder->keys();

$users = User::newQuery()
    ->whereVisibleTo($actor)
    ->whereIn('id', $ids)
    ->orderByRaw('FIELD(id' . str_repeat(', ?', count($ids)) . ')', $ids)
    ->limit(10)
    ->get();
```

## Support

This extension is under **minimal maintenance**.

It was developed for a client and released as open-source for the benefit of the community.
I might publish simple bugfixes or compatibility updates for free.

You can [contact me](https://clarkwinkelmann.com/flarum) to sponsor additional features or updates.

Support is offered on a "best effort" basis through the Flarum community thread.

## Links

- [GitHub](https://github.com/clarkwinkelmann/flarum-ext-scout)
- [Packagist](https://packagist.org/packages/clarkwinkelmann/flarum-ext-scout)
- [Discuss](https://discuss.flarum.org/d/30874)
