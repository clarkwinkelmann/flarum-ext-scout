<?php

namespace ClarkWinkelmann\Scout;

use Algolia\AlgoliaSearch\Config\SearchConfig;
use Algolia\AlgoliaSearch\SearchClient as Algolia;
use Algolia\AlgoliaSearch\Support\UserAgent;
use Flarum\Settings\SettingsRepositoryInterface;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\AlgoliaEngine;

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
