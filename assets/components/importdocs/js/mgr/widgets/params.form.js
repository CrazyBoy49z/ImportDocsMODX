ImportDocs.form.Panel = function (config) {

    ImportDocs.baseParamsField = new Ext.data.ArrayStore({
        url: ImportDocs.config.connectorUrl,
        root: 'results',
        fields: ['id', 'name'],
        errorReader: MODx.util.JSONReader,
        baseParams: {
            action: 'mgr/params/get',
            type: 'allParams'
        },
        autoDestroy: false,
        scope: this,
        autoLoad: true
    });

    ImportDocs.tvParamsField = new Ext.data.ArrayStore({
        url: ImportDocs.config.connectorUrl,
        root: 'results',
        fields: ['id', 'name'],
        errorReader: MODx.util.JSONReader,
        baseParams: {
            action: 'mgr/params/get',
            type: 'tvParams'
        },
        autoDestroy: false,
        scope: this,
        autoLoad: true
    });


    Ext.apply(config, {
        defaultType: 'textfield',
        labelAlign: 'top',
        style: {padding: '30px'},
        items: [{
            xtype: 'container',
            layout: 'form',
            items: [{
                xtype: 'modx-combo',
                fieldLabel: _('im.docs_unique_field'),
                allowBlank: true,
                name: 'uniqueField',
                displayField: 'name',
                valueField: 'id',
                store: ImportDocs.baseParamsField,
                triggerAction: 'all',
                hiddenName : 'uniqueField'
            },{
                xtype: 'textfield',
                allowBlank: true,
                fieldLabel: _('im.docs_separator_set'),
                name: 'separator'
            }]
        },{
            xtype: 'container',
            items: [{
                html: '<h4>' + _('im.docs_fields_images') + '</h4>',
                style: {margin: '15px 0'}
            }, {
                id: 'images_params_grid',
                xtype: 'im.docs-grid-fields',
                baseParams: {
                    action: 'mgr/params/get',
                    type: 'imagesParams'
                },
                comboStore: ImportDocs.tvParamsField
            }, {
                html: '<h4>' + _('im.docs_fields_related_resources') + '</h4>',
                style: {margin: '15px 0'}
            }, {
                id: 'related_params_grid',
                xtype: 'im.docs-grid-fields',
                baseParams: {
                    action: 'mgr/params/get',
                    type: 'relatedParams'
                },
                comboStore: ImportDocs.tvParamsField
            }, {
                html: '<h4>' + _('im.docs_fields_other_configuration') + '</h4>',
                style: {margin: '15px 0'}
            }, {
                xtype: 'im.docs-grid-fields',
                baseParams: {
                    action: 'mgr/params/get',
                    type: 'otherParams'
                },
                comboStore: ImportDocs.allFields
            }]
        }],
        buttons: [{
            xtype: 'button',
            cls: 'x-btn x-btn-small x-btn-icon-small-left primary-button x-btn-noicon',
            text: _('im.docs_update'),
            listeners: {
                click: function () {
                    this.saveParams();
                }, scope: this
            }
        }],
        listeners: {
            afterrender: function(){
                this.setup();
            }, scope: this
        }
    });

    ImportDocs.form.Panel.superclass.constructor.call(this, config)
};

Ext.extend(ImportDocs.form.Panel, MODx.FormPanel, {
    setup : function(){
        MODx.Ajax.request({
            url: ImportDocs.config.connectorUrl
            ,params: {
                action: 'mgr/params/get',
                type: 'getParams'
            },
            listeners: {
                'success': {
                    fn: function(response) {
                        this.getForm().setValues(response.results);

                        this.fireEvent('ready', response.results);
                        MODx.fireEvent('ready');
                    },
                    scope: this
                }
            }
        });
    },
    saveParams: function () {
        var grids = this.find('xtype', 'im.docs-grid-fields');


        var paramsFields = grids.map(function (grid) {

            var store = grid.getStore();
            var paramsStore = store.getRange();

            return {
                'type' : grid.baseParams.type,
                'data' : paramsStore.reduce(function (arr, item) {
                if (JSON.stringify(item.data) === '{}' || !item.get('id') || !item.get('consolation')) {
                    store.remove(item);
                } else {
                    arr.push(item.data);
                }
                return arr;
            }, [])};
        });


        paramsFields = JSON.stringify(paramsFields);

         this.getForm().submit({
             clientValidation: true,
             url: ImportDocs.config.connectorUrl,
             params: {
                 action: 'mgr/params/save',
                 type: 'baseParams',
                 data: paramsFields
             },
             success: function () {
                 Ext.MessageBox.alert(_('im.docs_title_alert_update'), _('im.docs_msg_success_update'));
             },
             failure: function (form, responce) {
                 Ext.MessageBox.alert(_('im.docs_title_alert_update'), _('im.docs_msg_error_update'));
             }
         })
    }
});

Ext.reg('im.docs-params-form', ImportDocs.form.Panel);