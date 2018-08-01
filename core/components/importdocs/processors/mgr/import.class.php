<?php
ini_set("upload_max_filesize","15M");
ini_set("post_max_size","15M");
ini_set("max_execution_time","1200"); //20 min.
ini_set("max_input_time","1200"); //20 min.
ini_set('auto_detect_line_endings',1);
date_default_timezone_set('Europe/Moscow');
setlocale (LC_ALL, 'ru_RU.UTF-8');

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

class ImportDocsProcessor extends modObjectProcessor
{
    public $languageTopics = ['importdocs'];

    /** @var  Import */
    private $import;


    /**
     *
     * @return array|string
     */
    public function process()
    {
        if (!isset($_FILES)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'ImportDocs: file is missing');
            return ['success' => false, 'message' => 'file is missing'];
        }

        require_once $this->modx->getOption('importdocs_core_path', null) . 'model/phpexcel/Classes/PHPExcel.php';
        require_once $this->modx->getOption('importdocs_core_path', null) . 'model/import/import.class.php';

        $filename = $_FILES['file']['tmp_name'];
        $file_type = PHPExcel_IOFactory::identify($filename);
        $objReader = PHPExcel_IOFactory::createReader($file_type);
        $objPHPExcel = $objReader->load($filename);

        $rows = $objPHPExcel->getActiveSheet()->toArray();

        $this->import = new Import($this->modx, $rows);

        $mode = $this->getProperty('mode');

        switch ($mode) {
            case ('insert'):
                $this->import->insertResources();
                break;
            case ('update'):
                $modeInsertUpdate = $this->getProperty('insert-update-mode');
                if($modeInsertUpdate === 'on') {
                    $this->import->updateResources(true);
                }else{
                    $this->import->updateResources();
                }
                break;
            case ('delete'):
                $this->import->deletingResource();
                break;
        }

        $this->modx->cacheManager->refresh();

        return $this->modx->toJSON(['success' => 1, 'data' => $this->import->log]);
    }
}


return 'ImportDocsProcessor';