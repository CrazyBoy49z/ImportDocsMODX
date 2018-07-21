ImportDocs.grid.Fields = function (config) {
    config = config || {};

    Ext.applyIf(config, {
        url: ImportDocs.config.connectorUrl,
        anchor: '100%',
        remoteSort: false,
        fields: this.getFields(config),
        columns: this.getColumns(config),
        paging: false,
        preventSaveRefresh: true,
        autoLoad: false,
        sm: new Ext.grid.CheckboxSelectionModel(),
        viewConfig: {
            forceFit: true,
            enableRowBody: true,
            showPreview: true,
            markDirty: true
        },
        buttons: [{
            xtype: 'button',
            text: _('im.docs_add_record'),
            listeners: {
                click: function () {
                    this.createNewParam();
                }, scope: this
            }, scope: this
        }]
    });

    ImportDocs.grid.Fields.superclass.constructor.call(this, config);
};

Ext.extend(ImportDocs.grid.Fields, MODx.grid.Grid, {
    getFields: function () {
        return ['id', 'consolation'];
    },
    getColumns: function (config) {
        return [{
            header: _('im.docs_field'),
            dataIndex: 'id',
            sortable: true,
            editor: {
                xtype: 'modx-combo',
                store: config.comboStore,
                mode: 'local',
                displayField: 'name',
                valueField: 'id',
                typeAhead: false,
                allowBlank: true,
                valueNotFoundText: _('im.docs_nonexistent_parameter')
            },
            renderer: function (val) {
                var record = this.editor.findRecord(this.editor.valueField, val);
                return record ? record.get(this.editor.displayField) : this.editor.valueNotFoundText;
            }
        }, {
            header: _('im.docs_consolation'),
            dataIndex: 'consolation',
            sortable: true,
            editor: {
                xtype: 'textfield',
                allowBlank: false
            }
        }, {
            dataIndex: 'delete',
            width: 10,
            sortable: false,
            renderer: function () {
                return '<button type="button" class="x-btn icon icon-trash-o"></button>'
            },
            listeners: {
                click: function (e) {
                    this.deleteParam();
                },
                scope: this
            }
        }];
    },
    createNewParam: function () {
        this.getStore().add(new (new Ext.data.Record.create([
            this.getFields()
        ])));
    },
    deleteParam: function () {
        this.getStore().remove(this.getSelectionModel().getSelections());
    }
});

Ext.reg('im.docs-grid-fields', ImportDocs.grid.Fields);