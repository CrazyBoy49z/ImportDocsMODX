<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
if (file_exists(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php')) {

    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php';
} else {
    require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.core.php';
}

require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';

require_once MODX_CONNECTORS_PATH . 'index.php';

$ImportDocs = $modx->getService('ImportDocs', 'ImportDocs', MODX_CORE_PATH . 'components/importdocs/model/');
$modx->lexicon->load('importdocs:default');


$corePath = $modx->getOption('im.docs_core_path', null, $modx->getOption('core_path') . 'components/importdocs/');

$path = $modx->getOption('processorsPath', $ImportDocs->config, $corePath . 'processors/');

$modx->getRequest();

$request = $modx->request;

$request->handleRequest([
    'processors_path' => $path,
    'location' => '',
]);
