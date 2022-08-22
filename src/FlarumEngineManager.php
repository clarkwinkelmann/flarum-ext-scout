<?php

namespace ClarkWinkelmann\Scout;

use Algolia\AlgoliaSearch\Config\SearchConfig;
use Algolia\AlgoliaSearch\SearchClient as Algolia;
use Algolia\AlgoliaSearch\Support\UserAgent;
use Exception;
use Flarum\Foundation\Paths;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\ConnectionInterface;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\AlgoliaEngine;
use TeamTNT\Scout\Engines\TNTSearchEngine;
use TeamTNT\TNTSearch\TNTSearch;

class FlarumEngineManager extends EngineManager
{
    /**
     * Same as original createAlgoliaDriver() but uses Flarum settings as source
     */
    public function createAlgoliaDriver(): AlgoliaEngine
    {
        $this->ensureAlgoliaClientIsInstalled();

        UserAgent::addCustomUserAgent('Laravel Scout', '9.4.7');

        /**
         * @var SettingsRepositoryInterface $settings
         */
        $settings = resolve(SettingsRepositoryInterface::class);

        $config = SearchConfig::create(
            $settings->get('clarkwinkelmann-scout.algoliaId'),
            $settings->get('clarkwinkelmann-scout.algoliaSecret')
        )->setDefaultHeaders(
            $this->defaultAlgoliaHeaders()
        );

        if (is_int($connectTimeout = $settings->get('clarkwinkelmann-scout.algoliaConnectTimeout'))) {
            $config->setConnectTimeout($connectTimeout);
        }

        if (is_int($readTimeout = $settings->get('clarkwinkelmann-scout.algoliaReadTimeout'))) {
            $config->setReadTimeout($readTimeout);
        }

        if (is_int($writeTimeout = $settings->get('clarkwinkelmann-scout.algoliaWriteTimeout'))) {
            $config->setWriteTimeout($writeTimeout);
        }

        return new AlgoliaEngine(Algolia::createWithConfig($config));
    }

    public function createTntsearchDriver(): TNTSearchEngine
    {
        if (!class_exists(TNTSearch::class) || !class_exists(TNTSearchEngine::class)) {
            throw new Exception('Please install the TNTSearch scout package: teamtnt/laravel-scout-tntsearch-driver.');
        }

        $storage = resolve(Paths::class)->storage . '/tntsearch';

        // TNTSearch won't create the folder automatically, so we need to do it
        if (!file_exists($storage)) {
            mkdir($storage);
        }

        /**
         * @var SettingsRepositoryInterface $settings
         */
        $settings = resolve(SettingsRepositoryInterface::class);

        $tnt = new TNTSearch();
        $tnt->loadConfig([
            'storage' => $storage,
            'searchBoolean' => true,
        ]);
        $tnt->setDatabaseHandle(resolve(ConnectionInterface::class)->getPdo());
        $tnt->maxDocs = $settings->get('clarkwinkelmann-scout.tntsearchMaxDocs') ?: 500;
        $tnt->fuzziness = (bool)$settings->get('clarkwinkelmann-scout.tntsearchFuzziness');
        // We could retrieve the defaults from the $tnt instance, but since we hard-code those in the javascript
        // it's safer to also hard-code them here so we stay consistent if the default ever change
        $tnt->fuzzy_distance = $settings->get('clarkwinkelmann-scout.tntsearchFuzzyDistance') ?: 2;
        $tnt->fuzzy_prefix_length = $settings->get('clarkwinkelmann-scout.tntsearchFuzzyPrefixLength') ?: 50;
        $tnt->fuzzy_max_expansions = $settings->get('clarkwinkelmann-scout.tntsearchFuzzyMaxExpansions') ?: 2;
        $tnt->asYouType = true;

        return new TNTSearchEngine($tnt);
    }

    /**
     * Same as original getDefaultDriver() but uses Flarum settings as source
     * @return mixed|string
     */
    public function getDefaultDriver()
    {
        /**
         * @var SettingsRepositoryInterface $settings
         */
        $settings = resolve(SettingsRepositoryInterface::class);

        if (is_null($driver = $settings->get('clarkwinkelmann-scout.driver'))) {
            return 'null';
        }

        return $driver;
    }
}
