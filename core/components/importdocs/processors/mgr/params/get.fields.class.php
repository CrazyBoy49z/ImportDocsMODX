<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

class ImportDocsParamsProcessor extends modProcessor
{
    public $languageTopics = ['importdocs'];


    /**
     * @return array|string
     */
    public function process()
    {
        $type = $this->getProperty('type');

        $results = [];

        switch ($type) {
            case 'baseFields':
                $results  = $this->_getBaseFields();
                break;
            case 'tvFields' :
                $results = $this->_getTvFields();
                break;
            case 'migxFields' :
                $results  = $this->_getMIGXFields();
                break;
            case 'allFields':
                $results = array_merge($this->_getBaseFields(), $this->_getTvFields(), $this->_getMIGXFields());
                break;
        }

        return $this->modx->toJSON(['results' => $results, 'success' => 1]);

    }

    /**
     * @return array|string
     */
    private function _getBaseFields()
    {
        $sql = "SHOW COLUMNS FROM {$this->modx->getFullTableName('site_content')} WHERE Field != 'id'";

        $statement = $this->modx->query($sql);
        $fields = $statement->fetchAll(PDO::FETCH_ASSOC);

        $arrayFields = [];

        if (!empty($fields)) {
            foreach ($fields as $field) {
                array_push($arrayFields, [$field['Field'], $field['Field']]);
            }
        }

        return $arrayFields;
    }

    /**
     * @return array|string
     */
    private function _getTvFields()
    {
        $query = $this->modx->newQuery('modTemplateVar');
        $query->select($this->modx->getSelectColumns('modTemplateVar', 'modTemplateVar', '', array('id', 'caption', 'name')));
        $query->where(['type:!=' => 'migx']);

        $tv_collection = $this->modx->getIterator('modTemplateVar', $query);

        $arrayFields = [];

        foreach ($tv_collection as $tv) {
            $name = $tv->get('name');
            $caption = $tv->get('caption');
            $id = $tv->get('id');

            array_push($arrayFields, [$id, "{$caption} [[tv.{$name}]]"]);
        }

        return $arrayFields;
    }

    /**
     * @return array|string
     */
    private function _getMIGXFields()
    {
        $modelpath = $this->modx->getOption('core_path') . 'components/migx/model/';
        $this->modx->addPackage('migx', $modelpath);

        $query = $this->modx->newQuery('modTemplateVar');
        $query->select($this->modx->getSelectColumns('modTemplateVar', 'modTemplateVar', '', ['id', 'caption', 'name', 'input_properties']));
        $query->where(['type:=' => 'migx']);

        $migx_tv_collection = $this->modx->getIterator('modTemplateVar', $query);

        //получаем конфигурации MIGX
        $query = $this->modx->newQuery('migxConfig');
        $query->select($this->modx->getSelectColumns('migxConfig', 'migxConfig', '', ['name', 'formtabs']));

        $migx_configs = $this->modx->getIterator('migxConfig', $query);


        $arrayFields = [];

        foreach ($migx_tv_collection as $migx) {
            $configsParam = $migx->get('input_properties')['configs'];
            $nameParam = $migx->get('name');
            $captionParam = $migx->get('caption');
            $idParam = $migx->get('id');

            foreach ($migx_configs as $config){

                if($config->get('name') == $configsParam){
                    $formtabs = $config->get('formtabs');

                    $tmp_migx_config_array = $this->modx->fromJSON($formtabs, true);
                    $tmp_migx_fields_array = $tmp_migx_config_array[0]['fields'];

                    foreach ($tmp_migx_fields_array as $key => $field) {
                        $name = $field['field'];
                        $caption = $field['caption'];

                        array_push($arrayFields, ["{$idParam}.{$name}", "{$captionParam}.{$caption} [[{$nameParam}.{$name}]]"]);
                    }
                }
            }
        }

        return $arrayFields;
    }
}

return 'ImportDocsParamsProcessor';