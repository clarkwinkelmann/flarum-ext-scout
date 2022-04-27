import app from 'flarum/admin/app';

app.initializers.add('clarkwinkelmann-scout', () => {
    app.extensionData.for('clarkwinkelmann-scout')
        .registerSetting({
            type: 'select',
            setting: 'clarkwinkelmann-scout.driver',
            options: {
                null: app.translator.trans('clarkwinkelmann-scout.admin.setting.driverOption.null'),
                algolia: app.translator.trans('clarkwinkelmann-scout.admin.setting.driverOption.algolia'),
                meilisearch: app.translator.trans('clarkwinkelmann-scout.admin.setting.driverOption.meilisearch'),
            },
            default: 'null',
            label: app.translator.trans('clarkwinkelmann-scout.admin.setting.driver'),
        })
        .registerSetting({
            type: 'text',
            setting: 'clarkwinkelmann-scout.prefix',
            label: app.translator.trans('clarkwinkelmann-scout.admin.setting.prefix'),
        })
        .registerSetting({
            type: 'text',
            setting: 'clarkwinkelmann-scout.algoliaId',
            label: app.translator.trans('clarkwinkelmann-scout.admin.setting.algoliaId'),
        })
        .registerSetting({
            type: 'text',
            setting: 'clarkwinkelmann-scout.algoliaSecret',
            label: app.translator.trans('clarkwinkelmann-scout.admin.setting.algoliaSecret'),
        })
        .registerSetting({
            type: 'text',
            setting: 'clarkwinkelmann-scout.algoliaConnectTimeout',
            label: app.translator.trans('clarkwinkelmann-scout.admin.setting.algoliaConnectTimeout'),
        })
        .registerSetting({
            type: 'text',
            setting: 'clarkwinkelmann-scout.algoliaReadTimeout',
            label: app.translator.trans('clarkwinkelmann-scout.admin.setting.algoliaReadTimeout'),
        })
        .registerSetting({
            type: 'text',
            setting: 'clarkwinkelmann-scout.algoliaWriteTimeout',
            label: app.translator.trans('clarkwinkelmann-scout.admin.setting.algoliaWriteTimeout'),
        })
        .registerSetting({
            type: 'text',
            setting: 'clarkwinkelmann-scout.meilisearchHost',
            label: app.translator.trans('clarkwinkelmann-scout.admin.setting.meilisearchHost'),
            placeholder: '127.0.0.1:7700',
        })
        .registerSetting({
            type: 'text',
            setting: 'clarkwinkelmann-scout.meilisearchKey',
            label: app.translator.trans('clarkwinkelmann-scout.admin.setting.meilisearchKey'),
        });
});
