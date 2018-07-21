ImportDocs.panel.Home = function (config) {
    config = config || {
        id: 'im.docs-panel-home'
    };

    ImportDocs.baseFields = new Ext.data.ArrayStore({
        url: ImportDocs.config.connectorUrl,
        root: 'results',
        errorReader: MODx.util.JSONReader,
        baseParams: {
            action: 'mgr/params/get.fields',
            type: 'baseFields'
        },
        autoDestroy: false,
        scope: this,
        autoLoad: true,
        fields: ['id', 'name']
    });

    ImportDocs.tvFields = new Ext.data.ArrayStore({
        url: ImportDocs.config.connectorUrl,
        root: 'results',
        errorReader: MODx.util.JSONReader,
        baseParams: {
            action: 'mgr/params/get.fields',
            type: 'tvFields'
        },
        autoDestroy: false,
        scope: this,
        autoLoad: true,
        fields: ['id', 'name']
    });

    ImportDocs.migxFields = new Ext.data.ArrayStore({
        url: ImportDocs.config.connectorUrl,
        root: 'results',
        errorReader: MODx.util.JSONReader,
        baseParams: {
            action: 'mgr/params/get.fields',
            type: 'migxFields'
        },
        autoDestroy: false,
        scope: this,
        autoLoad: true,
        fields: ['id', 'name']
    });

    ImportDocs.allFields = new Ext.data.ArrayStore({
        url: ImportDocs.config.connectorUrl,
        root: 'results',
        errorReader: MODx.util.JSONReader,
        baseParams: {
            action: 'mgr/params/get.fields',
            type: 'allFields'
        },
        autoDestroy: false,
        scope: this,
        autoLoad: true,
        fields: ['id', 'name']
    });


    Ext.apply(config, {
        baseCls: 'modx-formpanel',
        layout: 'anchor',
        hideMode: 'offsets',
        items: [{
            html: '<h2>' + _('importdocs') + '</h2>',
            cls: '',
            style: {margin: '15px 0'}
        }, {
            xtype: 'modx-tabs',
            defaults: {border: false, autoHeight: true},
            border: true,
            hideMode: 'offsets',
            items: [{
                title: _('im.docs_import'),
                layout: 'anchor',
                items: [{
                    xtype: 'im.docs-form-uploadfile',
                    cls: 'main-wrapper'
                }]
            }, {
                title: _('im.docs_import_params'),
                layout: 'anchor',
                xtype: 'im.docs-params-form'
            }, {
                title: _('im.docs_base_fields'),
                layout: 'anchor',
                xtype: 'im.docs-form-panel',
                cls: 'main-wrapper',
                items: [{
                    xtype: 'im.docs-grid-fields',
                    baseParams: {
                        action: 'mgr/params/get',
                        type: 'baseFields'
                    },
                    comboStore:  ImportDocs.baseFields
                }]
            }, {
                title: _('im.docs_tv_fields'),
                layout: 'anchor',
                cls: 'main-wrapper',
                xtype: 'im.docs-form-panel',
                items: [{
                    xtype: 'im.docs-grid-fields',
                    baseParams: {
                        action: 'mgr/params/get',
                        type: 'tvFields'
                    },
                    comboStore: ImportDocs.tvFields
                }]
            }, {
                title: _('im.docs_migx_fields'),
                layout: 'anchor',
                xtype: 'im.docs-form-panel',
                cls: 'main-wrapper',
                items: [{
                    xtype: 'im.docs-grid-fields',
                    baseParams: {
                        action: 'mgr/params/get',
                        type: 'migxFields'
                    },
                    comboStore: ImportDocs.migxFields
                }]
            }]
        }]
    });
    ImportDocs.panel.Home.superclass.constructor.call(this, config);
}
;
Ext.extend(ImportDocs.panel.Home, MODx.Panel);
Ext.reg('im.docs-panel-home', ImportDocs.panel.Home);


ImportDocs.form.Item = function (config) {

    Ext.applyIf(config, {
        xtype: 'modx-formpanel',
        buttons: [{
            cls: 'x-btn x-btn-small x-btn-icon-small-left primary-button x-btn-noicon',
            xtype: 'button',
            text: _('im.docs_update'),
            listeners: {
                click: function () {
                    this.saveParams();
                }, scope: this
            }
        }]
    });

    ImportDocs.form.Item.superclass.constructor.call(this, config);
};

Ext.extend(ImportDocs.form.Item, MODx.FormPanel, {
    saveParams: function () {
        var grid = this.find('xtype', 'im.docs-grid-fields')[0];
        var store = grid.getStore();
        var paramsFields = store.getRange();


        paramsFields = paramsFields.reduce(function (arr, item) {
            if (JSON.stringify(item.data) === '{}' || !item.get('id') || !item.get('consolation')) {
                store.remove(item);
            } else {
                arr.push(item.data);
            }
            return arr;
        }, []);

        paramsFields = JSON.stringify(paramsFields);

        this.getForm().submit({
            clientValidation: true,
            url: ImportDocs.config.connectorUrl,
            params: {
                action: 'mgr/params/save',
                type: grid.baseParams.type,
                data: paramsFields
            },
            success: function () {
                Ext.MessageBox.alert(_('im.docs_title_alert_update'), _('im.docs_msg_success_update'));
                Ext.getCmp('images_params_grid').getStore().reload();
                Ext.getCmp('related_params_grid').getStore().reload();
                ImportDocs.baseParamsField.reload();
                ImportDocs.tvParamsField.reload();
            },
            failure: function (form, responce) {
                Ext.MessageBox.alert(_('im.docs_title_alert_update'), _('im.docs_msg_error_update'));
            }
        })
    }
});
Ext.reg('im.docs-form-panel', ImportDocs.form.Item);
