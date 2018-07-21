<?php

/**
 * The home manager controller for ImportDocs.
 *
 */
class ImportDocsImportManagerController extends modExtraManagerController
{
    /** @var ImportDocs $ImportDocs */
    public $ImportDocs;


    /**
     *
     */
    public function initialize()
    {
        $this->ImportDocs = $this->modx->getService('ImportDocs', 'ImportDocs', MODX_CORE_PATH . 'components/importdocs/model/');
        parent::initialize();
    }


    /**
     * @return array
     */
    public function getLanguageTopics()
    {
        return ['importdocs:default'];
    }


    /**
     * @return bool
     */
    public function checkPermissions()
    {
        return true;
    }


    /**
     * @return null|string
     */
    public function getPageTitle()
    {
        return $this->modx->lexicon('importdocs');
    }


    /**
     * @return void
     */
    public function loadCustomCssJs()
    {
        $this->addCss($this->ImportDocs->config['cssUrl'] . 'mgr/main.css');
        $this->addJavascript($this->ImportDocs->config['jsUrl'] . 'mgr/importdocs.js');
        $this->addJavascript($this->ImportDocs->config['jsUrl'] . 'mgr/widgets/fields.grid.js');
        $this->addJavascript($this->ImportDocs->config['jsUrl'] . 'mgr/widgets/params.form.js');
        $this->addJavascript($this->ImportDocs->config['jsUrl'] . 'mgr/widgets/uploadfile.form.js');
        $this->addJavascript($this->ImportDocs->config['jsUrl'] . 'mgr/widgets/home.panel.js');
        $this->addJavascript($this->ImportDocs->config['jsUrl'] . 'mgr/widgets/log.window.js');
        $this->addJavascript($this->ImportDocs->config['jsUrl'] . 'mgr/sections/home.js');


        $this->addHtml('<script type="text/javascript">
        ImportDocs.config = ' . $this->modx->toJSON($this->ImportDocs->config) . ';
        ImportDocs.config.connector_url = "' . $this->ImportDocs->config['connectorUrl'] . '";
        Ext.onReady(function() {MODx.load({ xtype: "im.docs-page-home"});});
        </script>');
    }


    /**
     * @return string
     */
    public function getTemplateFile()
    {
        $this->content .= '<div id="im.docs-panel-home-div"></div>';

        return '';
    }
}