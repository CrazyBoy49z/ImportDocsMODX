<?php
/**
 * Class ImportDocsMainController
 */
abstract class ImportDocsMainController extends modExtraManagerController {
    /** @var ImportDocs $ImportDocs */
    public $ImportDocs;
    /**
     * @return void
     */
    public function initialize() {
        $corePath = $this->modx->getOption('importdocs_core_path', null, '');
        require_once $corePath . 'model/import';

        $this->ImportDocs = new ImportDocs($this->modx);
        $this->addCss($this->ImportDocs->config['cssUrl'] . 'mgr/main.css');
        $this->addJavascript($this->ImportDocs->config['jsUrl'] . 'mgr/importdocs.js');
        $this->addHtml('<script type="text/javascript">
		Ext.onReady(function() {
			ImportDocs.config = ' . $this->modx->toJSON($this->ImportDocs->config) . ';
			ImportDocs.config.connector_url = "' . $this->ImportDocs->config['connectorUrl'] . '";
		});
		</script>');
        parent::initialize();
    }
    /**
     * @return array
     */
    public function getLanguageTopics() {
        return array('importdocs:default');
    }
    /**
     * @return bool
     */
    public function checkPermissions() {
        return true;
    }
}
/**
 * Class IndexManagerController
 */
class IndexManagerController extends ImportDocsMainController {
    /**
     * @return string
     */
    public static function getDefaultController() {
        return 'home';
    }
}