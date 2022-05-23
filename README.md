# Scout Search for Flarum

[![MIT license](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/clarkwinkelmann/flarum-ext-scout/blob/master/LICENSE.md) [![Latest Stable Version](https://img.shields.io/packagist/v/clarkwinkelmann/flarum-ext-scout.svg)](https://packagist.org/packages/clarkwinkelmann/flarum-ext-scout) [![Total Downloads](https://img.shields.io/packagist/dt/clarkwinkelmann/flarum-ext-scout.svg)](https://packagist.org/packages/clarkwinkelmann/flarum-ext-scout) [![Donate](https://img.shields.io/badge/paypal-donate-yellow.svg)](https://www.paypal.me/clarkwinkelmann)

Integrates [Laravel Scout](https://laravel.com/docs/9.x/scout) with [Flarum](https://flarum.org/) discussion and user search.

Just like with Laravel, the data is automatically synced with the search index every time a model is updated in Flarum.
You only need to manually import data when you enable the extension (see commands below).

The external search driver is used server-side to filter down the MySQL results, so it should still be compatible with every other extension and search gambits.

Algolia and Meilisearch drivers are included in the extension.
The Scout database and collection drivers cannot be used (they would be worst than Flarum's built-in database search).

For convenience, this extension already includes the PHP SDK of both Algolia and Meilisearch.
However, if you are not running the latest Meilisearch version you might need to explicitly install an older version of the SDK.

If you are regularly running `composer update` on all your dependencies, you should add an explicit requirement for the Meilisearch SDK in your `composer.json` because the extension requires `*` which might jump to a newer Meilisearch SDK version as soon as it comes out.

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
