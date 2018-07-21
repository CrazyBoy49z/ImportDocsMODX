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
        $corePath = $this->modx->getOption('im.docs_core_path', null, $this->modx->getOption('core_path') . 'components/importdocs/');
        require_once $corePath . 'model/importdocs/importdocs.class.php';
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