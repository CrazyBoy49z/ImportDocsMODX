<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

class Import{

    /** @var modX $modx */
    public $modx;

    /** @var array log  */
    public $log = [];

    /** @var array Records  */
    public $Records = [];

    /** @var array translitTable  */
    private $translitTable = [];


    /** @var array $MIGXconfigs */
    private $MIGXConfigs = [];


    /*
     *
     * @return boolean
     */
    function __construct(modX &$modx,  array $rows = []){
        $this->modx =& $modx;

        require_once $this->modx->getOption('importdocs_core_path', null) . 'model/import/records.class.php';
        $this->translitTable = require_once $this->modx->getOption('importdocs_core_path', null) . 'model/phpexcel/translitTable.php';

        $rowHeaders = $rows[0];
        unset($rows[0]);

        $params = $this->getParameters();

        $this->Records = new Records($this->modx, $params, $rowHeaders, $rows);

        $this->MIGXConfigs = $this->getMIGXConfigurations($this->Records->templateVarMIGX);
    }


    /**
     *
     * @return array
     */
    protected function getParameters(){

        $paramsFilePath = $this->modx->getOption('importdocs_core_path', null) . 'model/params.json';
        $paramsFile = file_get_contents($paramsFilePath);

        if($paramsFile === false) {
            $this->modx->log(MODX::LOG_LEVEL_ERROR, 'ImportDocs: Parameters file not found.');
            exit ($this->modx->toJSON(['success' => 0, 'message' => 'Parameters file not found.']));
        }

        $params = $this->modx->fromJSON($paramsFile);

        $tmpParams = [];

        foreach ($params as $param => $values) {
            if($param == 'otherParams'){
                foreach($values as $value){
                    $type = null;
                    if(preg_match('/^(\d+).([\w.]+)$/', $value['id'], $arrayPartKey)){
                        $type = 'migx';
                        $tmpParams['default'][$type][$arrayPartKey[1]][$arrayPartKey[2]] = mb_strtolower(trim($value['consolation']));
                        continue;
                    }else if(is_numeric($value['id'])){
                        $type = 'tv';
                    }else{
                        $type = 'base';
                    }
                    $tmpParams['default'][$type][$value['id']] = mb_strtolower(trim($value['consolation']));
                }
            } else if ($param !== 'uniqueField' && $param !== 'separator') {
                foreach ($values as $value) {
                    $tmpParams[$param][$value['id']] = mb_strtolower(trim($value['consolation']));
                }
            } else {
                $tmpParams[$param] = $values;
            }
        }

        unset($params_file);

        return $tmpParams;

    }

    /**
     * @param boolean $modeInsert
     * @return boolean
     */
    public function updateResources($modeInsert = false){
        foreach ($this->Records->records as &$record){
            $keyUniqueField = $this->Records->getKeyUniqueField();
            $uniqueResourceFields = $this->getAllTypeUniqueField();

            $record['base']['id'] = array_search($record['uniqueField'][$keyUniqueField], $uniqueResourceFields);

            if (!$record['base']['id']) {
                if ($modeInsert) {
                    $this->insertResource($record);
                    continue;
                } else {
                    $this->writeToLog("Ошибка! Ресурс с уникальным полем {$record['uniqueField'][0]} не обновлён. Рeсурс не найден.");
                }

            }
            $record['migx'] = $this->getJOINStringMIGXField($record['migx']);
            $record['tv']['tvs'] = 1;

            $updateObject = $this->modx->runProcessor('resource/update', $record['base'] + $record['tv'] + $record['migx']);

            if ($updateObject->isError()) {
                $this->writeToLog("Ошибка! Ресурс с уникальным полем {$record['uniqueField'][0]} не обновлён. Детали: {$updateObject->getMessage()}");
                continue;
            }
            $this->writeToLog("Ресурс {$record['id']} с уникальным полем {$record['uniqueField'][0]} успешно обновлён.");
        }

        $this->updateFieldsWithRelatedResources();

        return true;
    }


    /**
     *
     * @return boolean
     */
    public function insertResources(){

        foreach ($this->Records->records as $record){
            $this->insertResource($record);
        }

        $this->updateFieldsWithRelatedResources();

        return true;
    }


    /**
     * @param $record
     * @return boolean
     */
    protected function insertResource(&$record){

        if (!isset($record['base']['pagetitle'])) {
            $this->writeToLog("Ошибка! Ресурс с уникальным полем {$record['uniqueField'][0]} не добавлен. Отсутствует [[pagetitle]]");
        }

        if (!isset($record['base']['alias'])) {
            $record['base']['alias'] = $this->makeAlias($record['base']['pagetitle']);
        }
        $record['base']['alias'] = $this->getUniqueAlias($record['base']['alias']);

        $record['tv']['tvs'] = 1;
        $record['migx'] = $this->getJOINStringMIGXField($record['migx']);

        $createObject = $this->modx->runProcessor('resource/create', $record['base'] + $record['tv'] + $record['migx']);

        if ($createObject->isError()) {
            $this->writeToLog("Ошибка! Ресурс с уникальным полем {$record['uniqueField'][0]} не добавлен. Детали: {$createObject->getMessage()}");
            return false;
        }

        $object = $createObject->getObject();
        $idResource = $object['id'];
        if (!$idResource) return false;
        $record['base']['id'] = $idResource;
        $this->writeToLog("Ресурс {$idResource} с уникальным полем {$record['uniqueField'][0]} успешно добавлен.");

        return true;
    }


    /**
     *
     * @return boolean
     */
    public function deletingResource(){

        $keyUniqueField = $this->Records->getKeyUniqueField();
        $tmpFieldsResource = $this->getAllTypeUniqueField();

        foreach ($this->Records->records as $record) {
            if (isset($record['uniqueField'])) {
                $idResource = array_search($record['uniqueField'][$keyUniqueField], $tmpFieldsResource);

                if ($idResource !== false) {

                    $deleteObject = $this->modx->runProcessor('resource/delete', ['id' => $idResource]);

                    if ($deleteObject->isError()) {
                        $this->writeToLog("Ошибка! Ресурс $idResource не удалён. Детали: {$deleteObject->getMessage()}");
                        continue;
                    }

                    $this->writeToLog("Ресурс {$idResource} с уникальным полем успешно удалён.");
                }
            }
        }

        unset($resultsTmp);

        return true;
    }


    /**
     * @param string $pagetitle
     * @return string
     */
    protected function makeAlias($pagetitle){

        if (!empty($this->translitTable) && is_array($this->translitTable)) {
            $alias = strtr($pagetitle, $this->translitTable);
        } else {
            $alias = $pagetitle;
        }

        $alias = strtolower($alias);
        $alias = preg_replace('~[^-a-z0-9_]+~u', '-', $alias);
        $alias = trim($alias, "-");

        $alias = $this->getUniqueAlias($alias);

        return $alias;
    }

    /**
     * @param string $alias
     * @return string
     */
    protected function getUniqueAlias($alias){
       if ($this->modx->getCount('modResource', array('alias' => $alias)) != 0) {
            $count = 1;
            $newAlias = $alias;
            while ($this->modx->getCount('modResource', array('alias' => $newAlias)) != 0) {
                $newAlias = $alias;
                $newAlias .= '-' . $count;
                $count++;
            }
            $alias = $newAlias;
        }

        return $alias;
    }


    /**
     * @param array $MIGXData
     * @return array
     */
    protected function getJOINStringMIGXField($MIGXData){
        foreach ($MIGXData as $keyField => &$field){

            $configFields = $this->MIGXConfigs[$keyField];

            foreach ($field as $keyValue => &$value){
                $value['MIGX_id'] = $keyValue + 1;

                foreach ($configFields as $configField){
                    if(!isset($value[$configField])){
                        $value[$configField] = '';
                    }
                }

            }

            $field = $this->modx->toJSON($field);
        };

        return $MIGXData;
    }

    /**
     * @param array $templateVarMIGX
     * @return array
     *
     */
    protected function getMIGXConfigurations($templateVarMIGX = []){

        $modelpath = $this->modx->getOption('core_path') . 'components/migx/model/';
        $this->modx->addPackage('migx', $modelpath);

        $query = $this->modx->newQuery('modTemplateVar');
        $query->select($this->modx->getSelectColumns('modTemplateVar', 'modTemplateVar', '', ['id', 'input_properties']));
        $query->where(['type:=' => 'migx', 'id:IN' => $templateVarMIGX]);

        $tvCollection = $this->modx->getIterator('modTemplateVar', $query);
        $tmpConfigsTv = [];
        foreach ($tvCollection as $tmpl) {
            $configsParam = $tmpl->get('input_properties')['configs'];
            $tmpConfigsTv[$tmpl->get('id')] = $configsParam;
        }

        $query = $this->modx->newQuery('migxConfig');
        $query->select($this->modx->getSelectColumns('migxConfig', 'migxConfig', '', ['name', 'formtabs']));
        $query->where(['name:IN' => $tmpConfigsTv, 'deleted:!=' => 1]);

        $migxConfigs = $this->modx->getIterator('migxConfig', $query);
        $tmpMIGXFields = [];

        foreach ($migxConfigs as $config){
            $key = array_search($config-> get('name'), $tmpConfigsTv);
            $formtabs = $config-> get('formtabs');
            $tmpFormTabs = $this->modx->fromJSON($formtabs, true);
            $tmpFormTabsFields = $tmpFormTabs[0]['fields'];
            foreach ($tmpFormTabsFields as $field) {
                $name = $field['field'];
                $tmpMIGXFields['tv'.$key][] = $name;
            }
        }

        return $tmpMIGXFields;
    }


    /**
     * @return boolean
     */
    protected function updateFieldsWithRelatedResources(){
       $tmpFieldsResource = $this->getAllTypeUniqueField();

        foreach ($this->Records->records as &$record) {
            if (isset($record['related'])) {
                foreach ($record['related'] as $keyFields => $param) {
                    $tmpRelatedResourceRecords = [];
                    if(preg_match('/^(\d+).([\w.]+)$/', $keyFields, $arrayPartsField)){
                        foreach ($param as $indexParam => $value){
                            foreach ($value as $indexValue => $v) {
                                $idResourceRelated = array_search($v, $tmpFieldsResource);
                                $tmpRelatedResourceRecords[$indexParam][$indexValue] = (string)$idResourceRelated;
                            }

                        }
                        $tv = $this->modx->fromJSON($record['migx']['tv'.$arrayPartsField[1]]);

                        if(empty($tv)){
                            $fieldsTv = $this->MIGXConfigs['tv'.$arrayPartsField[1]];
                            $countFields = count($tmpRelatedResourceRecords);
                            $counter = 0;

                            while($counter < $countFields) {
                                foreach ($fieldsTv as $field) {
                                    $tv[$counter][$field] = '';
                                }
                                $counter++;
                            }
                        }

                        if(empty($tv)) continue;

                        foreach ($tv as $indexField => &$field){
                            if(isset($tmpRelatedResourceRecords[$indexField])) {
                                $field[$arrayPartsField[2]] = implode('||', $tmpRelatedResourceRecords[$indexField]);
                            }
                        }

                        $record['tv']['tv' .$arrayPartsField[1]] = $this->modx->toJSON($tv);

                    }else {
                        foreach ($param as $fields) {
                            $idResourceRelated = array_search($fields, $tmpFieldsResource);
                            if ($idResourceRelated !== false) {
                                $tmpRelatedResourceRecords[] = $idResourceRelated;
                            }
                        }
                        $record['tv']['tv' .$keyFields] = $tmpRelatedResourceRecords;
                    }

                    if (empty($tmpRelatedResourceRecords)) continue;
                }

                $createObject = $this->modx->runProcessor('resource/update', $record['base'] + $record['tv']);

                if ($createObject->isError()) {
                    $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'ImportDocs: ' . $createObject->getMessage());
                }
            }
        }

        return true;
    }

    /**
     *
     * @return array
     */
    protected function getAllTypeUniqueField()
    {
        $keyUniqueField = $this->Records->getKeyUniqueField();
        $tmpFieldsResource = [];

        switch ($this->Records->getTypeUniqueField()) {
            case('migx'):
                preg_match('/^(\d+).([\w.]+)$/', $keyUniqueField, $arrayPartsField);
                $query = $this->modx->newQuery('modTemplateVarResource');
                $query->where(['tmplvarid' => $arrayPartsField[1]]);
                $query->select(['contentid', 'value']);

                $migxs = $this->modx->getIterator('modTemplateVarResource', $query);

                if (!$migxs) return $tmpFieldsResource;

                foreach ($migxs as $migx) {
                    $value = $this->modx->fromJSON($migx->get('value'));


                    foreach ($value as $val) {
                        $tmpFieldsResource[$migx->get('contentid')][] = $val[$arrayPartsField[2]];
                    }
                }

                break;
            case ('tv'):
                $query = $this->modx->newQuery('modTemplateVarResource');
                $query->where(['tmplvarid' => $keyUniqueField]);
                $query->select(['contentid', 'value']);

                $tvs = $this->modx->getIterator('modTemplateVarResource', $query);

                if (!$tvs) return $tmpFieldsResource;

                foreach ($tvs as $tv) {
                    $tmpFieldsResource[$tv->get('contentid')] = $tv->get('value');
                }
                break;
            case ('base'):
                $query = $this->modx->newQuery('modResource');
                $query->select(['id', $keyUniqueField]);

                $fields = $this->modx->getIterator('modResource', $query);

                if (!$fields) return $tmpFieldsResource;

                foreach ($fields as $field) {
                    $tmpFieldsResource[$field->get('id')] = $field->get($keyUniqueField);
                }
                break;
        }


        return $tmpFieldsResource;
    }


    private function writeToLog($message = null, $error = null){
        if($error){
            $this->log[] = "Ошибка: {$error['details']}. {$message}";
        }else{
            $this->log[] = $message;
        }

    }
}