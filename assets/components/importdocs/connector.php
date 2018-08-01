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

$corePath = $modx->getOption('importdocs_core_path', null, '');
$ImportDocs = $modx->getService('ImportDocs', 'ImportDocs', $corePath . '/model/');

$modx->lexicon->load('importdocs:default');

$path = $modx->getOption('processorsPath', $ImportDocs->config, $corePath . 'processors/');

$modx->getRequest();

$request = $modx->request;

$request->handleRequest([
    'processors_path' => $path,
    'location' => '',
]);
