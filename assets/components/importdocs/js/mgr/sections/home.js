ImportDocs.page.Home = function (config) {
    config = config || {
        id : 'im.docs-page-home'
    };
    Ext.applyIf(config, {
        components: [{
            xtype: 'im.docs-panel-home',
            renderTo: 'im.docs-panel-home-div'
        }]
    });
    ImportDocs.page.Home.superclass.constructor.call(this, config);
};
Ext.extend(ImportDocs.page.Home, MODx.Component);
Ext.reg('im.docs-page-home', ImportDocs.page.Home);