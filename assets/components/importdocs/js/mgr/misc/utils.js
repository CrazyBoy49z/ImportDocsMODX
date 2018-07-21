Ext.extend(ImportDocs, MODx, {
    listBaseParams : function () {
        return new Ext.data.ArrayStore({
            url: ImportDocs.config.connectorUrl,
            root: 'results',
            fields: ['name', 'id'],
            errorReader: MODx.util.JSONReader,
            baseParams: {
                action: 'mgr/params/get',
                type: 'allParams'
            },
            autoDestroy: false,
            scope: this,
            autoLoad: true
        });
    }
})