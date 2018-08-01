<?php

class ImportDocs
{
    /** @var modX $modx */
    public $modx;

    /**
     * @param modX $modx
     * @param array $config
     */
    function __construct(modX &$modx, array $config = [], array $rows = [], array $rowHeaders = [])
    {
        $this->modx =& $modx;
        $corePath = $this->modx->getOption('importdocs_core_path', null);
        $assetsUrl = $this->modx->getOption('importdocs_assets_path', null);

        $this->config = array_merge([
            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'processorsPath' => $corePath . 'processors/',

            'connectorUrl' => $assetsUrl . 'connector.php',
            'assetsUrl' => $assetsUrl,
            'cssUrl' => $assetsUrl . 'css/',
            'jsUrl' => $assetsUrl . 'js/',
        ], $config);

        $this->modx->addPackage('importdocs', $this->config['modelPath']);
        $this->modx->lexicon->load('importdocs:default');
    }
}