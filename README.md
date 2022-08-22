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

All CLI commands from Scout are available, however you need to replace the model class names with the special classes built into this extension (`Flarum\User\User` becomes `ClarkWinkelmann\Scout\Model\User`, `Flarum\Discussion\Discussion` becomes `ClarkWinkelmann\Scout\Model\Discussion`, etc.):

```
php flarum scout:import-all           Import all Flarum models into the search index
                                      (a shortcut to scout:import with all the correct class names)
php flarum scout:flush {model}        Flush all of the model's records from the index
php flarum scout:import {model}       Import the given model into the search index
php flarum scout:index {name}         Create an index (generally not needed)
php flarum scout:delete-index {name}  Delete an index (generally not needed)
```

A future version of this extension will include an extender for other extensions to add indexable data.

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
