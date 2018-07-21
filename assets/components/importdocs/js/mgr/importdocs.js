var ImportDocs = function (config) {
    config = config || {};
    ImportDocs.superclass.constructor.call(this, config);
};
Ext.extend(ImportDocs, Ext.Component, {
    page: {}, grid: {}, tree: {}, panel: {}, combo: {}, config: {}, view: {}, form: {},renderer: {}
});

Ext.reg('importdocs', ImportDocs);

ImportDocs = new ImportDocs();
