ImportDocs.form.Panel = function (config) {
    config = config || {
        id: 'im.docs-form-uploadfile'
    };

    Ext.applyIf(config, {
        id: 'im.form_fileuploadfield',
        layout: 'form',
        fileUpload: true,
        enctype: 'multipart/form-data',
        xtype: 'modx-formpanel',
        style: {padding: '30px'},
        items: [{
            xtype: 'fileuploadfield',
            fieldLabel: _('im.docs_file'),
            name: 'file',
            anchor: '100%',
            allowBlank: false
        }, {
            xtype: 'container',
            layout: "column",
            items: [{
                html: '<h4>' + _('im.docs_mode') + '</h4>'
            },{
                xtype: 'container',
                id: 'im.insert-update-mode',
                layout: 'form',
                items: [{
                    xtype: 'checkbox',
                    width: '300',
                    fieldLabel: _('im.insert_update'),
                    name : 'insert-update-mode',
                    labelStyle: 'width:230px;'
                }]
            }],
            listeners: {
                afterrender: function () {

                    var radiogroup = MODx.load({
                        xtype: 'radiogroup',
                        items: [{
                            xtype: 'radio',
                            boxLabel: _('im.docs_mode_update'),
                            name: 'mode',
                            inputValue: 'update',
                            checked: true
                        }, {
                            xtype: 'radio',
                            boxLabel: _('im.docs_mode_insert'),
                            name: 'mode',
                            inputValue: 'insert'
                        }, {
                            xtype: 'radio',
                            boxLabel: _('im.docs_mode_delete'),
                            name: 'mode',
                            inputValue: 'delete'
                        }],
                        listeners: {
                            change: function (container, target) {
                                var c = Ext.getCmp('im.insert-update-mode');

                                if(target.inputValue !== 'update'){
                                    c.hide();
                                }else{
                                    c.show();
                                }
                            }
                        }
                    });
                    this.insert(1, radiogroup);
                }
            }
        },{
            xtype: 'combo',
            fieldLabel: _('im.docs_format_file'),
            store: new Ext.data.ArrayStore({
                fields: ['format', 'name'],
                data: [
                    ['XLS', 'XLS / XLSX']
                ]
            }),
            mode: 'local',
            displayField: 'name',
            valueField: 'format',
            typeAhead: true,
            editable: true,
            name: 'format',
            listeners: {
                afterrender: function (combo) {
                    var store = combo.getStore();
                    if (!combo.getValue()) {
                        combo.setValue(store.getAt(0).get(combo.valueField));
                    }
                }
            }
        }, {
            buttonAlign: 'right',
            buttons: [{
                xtype: 'button',
                text: _('im.docs_submit_file'),
                listeners: {
                    click: function () {
                        this.startImport();
                    }, scope: this
                },
                style: {margin: '15px 30px'},
                cls: 'primary-button',
            }]
        }]
    });


    ImportDocs.form.Panel.superclass.constructor.call(this, config);
};

Ext.extend(ImportDocs.form.Panel, MODx.FormPanel, {
    startImport: function () {
        this.getForm().submit({
            clientValidation: true,
            url: ImportDocs.config.connectorUrl,
            params: {
                action: 'mgr/import',
            },
            success: function (form, action) {
                var response = action.response.responseText;

                response = JSON.parse(response);
                response.data = response.data.map(function(record){
                    var div = document.createElement('div');
                    var text = document.createTextNode(record);
                    div.appendChild(text);
                    return div;
                });

                var win = new Ext.Window({
                    title: _('im.log'),
                    modal: true,
                    width: 750,
                    height: 500,
                    preventBodyReset: true,
                    contentEl: response.data
                });
                win.show();
            },
            failure: function (form, responce) {
                Ext.MessageBox.alert(_('im.docs_title_alert_update'), _('im.docs_msg_error_update'));
            }
        })

    }
});
Ext.reg('im.docs-form-uploadfile', ImportDocs.form.Panel);