<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

class ImportDocsProcessor extends modObjectProcessor
{
    public $languageTopics = ['importdocs'];

    protected $params = [];
    protected $translit = [];
    protected $rows = [];
    protected $indices = [];
    protected $records = [];
    protected $migxConfigs = [];

    protected $log = [];


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
        $this->translit = include_once $this->modx->getOption('importdocs_core_path', null) . 'model/phpexcel/translitTable.php';

        $this->params = $this->loadParams();

        $modelpathMigx = $this->modx->getOption('core_path') . 'components/migx/model/';
        $this->modx->addPackage('migx', $modelpathMigx);

        $filename = $_FILES['file']['tmp_name'];
        $file_type = PHPExcel_IOFactory::identify($filename);
        $objReader = PHPExcel_IOFactory::createReader($file_type);
        $objPHPExcel = $objReader->load($filename);

        $this->rows = $objPHPExcel->getActiveSheet()->toArray();
        $this->rows[0] = array_map('mb_strtolower', $this->rows[0]);

        $this->indices = $this->getIndicesColumns();

        $this->records = $this->getArraysRecord();

        $this->importResources();

        return $this->modx->toJSON(['success' => 1, 'data' => $this->log]);
    }

    /**
     *
     * @return array
     */
    protected function loadParams()
    {
        $params_file_path = $this->modx->getOption('importdocs_core_path', null) . 'model/params.json';

        $params_file = file_get_contents($params_file_path);

        if($params_file === false) {
            $this->modx->log(MODX::LOG_LEVEL_ERROR, 'ImportDocs: Parameter file not found.');
            exit ($this->modx->toJSON(['success' => 0, 'message' => 'Parameter file not found.']));
        }

        $params = $this->modx->fromJSON($params_file);

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
     *
     * @return array/array
     */
    protected function getIndicesColumns()
    {
        $indices = [];
        $headerRows = $this->rows[0];

        foreach ($headerRows as $row) {
            $key = null;
            $type = null;

            if ($key = array_search($row, $this->params['migx_fields'])) {

                $type = 'migx';

            } else if ($key = array_search($row, $this->params['tv_fields'])) {

                $type = 'tv';

            } else if ($key = array_search($row, $this->params['base_fields'])) {

                $type = 'base';
            }


            if ($key && $type) {
                $indices[] = [$key => $type];
            }
        }

        unset($this->rows[0]);

        return $indices;
    }


    /**
     *
     * @return array/array
     */
    protected function getMigxConfigs($tmpTemplateVarMIGX){

        $modelpath = $this->modx->getOption('core_path') . 'components/migx/model/';
        $this->modx->addPackage('migx', $modelpath);

        $query = $this->modx->newQuery('modTemplateVar');
        $query->select($this->modx->getSelectColumns('modTemplateVar', 'modTemplateVar', '', ['id', 'input_properties']));
        $query->where(['type:=' => 'migx', 'id:IN' => $tmpTemplateVarMIGX]);

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
     *
     *
     * @return array/array
     */
    protected function getArraysRecord()
    {
        $tmpTemplateVarMIGX = [];
        $records = [];

        $keyUniqueField = $this->params['uniqueField'];

        foreach ($this->rows as $row) {

            $tmpRecord = [];

            foreach ($this->indices as $index => $indexValue) {

                if (!$row[$index]) continue;

                $key = key($indexValue);
                $type = $indexValue[$key];

                if (isset($this->params['relatedParams'][$key])) {

                    $tmpRecord['related'][$key] = $this->getRelatedParam($key, $row[$index]);

                    continue;
                }

                $imagesPath = (isset($this->params['imagesParams'][$key])) ? $this->params['imagesParams'][$key] : null;

                switch ($type) {

                    case ('migx'):
                        preg_match('/^(\d+).([\w.]+)$/', $key, $arrayPartsField);

                        $keyField = $arrayPartsField[1];
                        $field = $arrayPartsField[2];
                        $this->separatorMigx($row[$index]);

                        foreach ($row[$index] as $indexField => &$valueField) {

                            if ($imagesPath) {
                                $valueField = $imagesPath . $valueField;
                            }

                            $tmpRecord[$type]['tv' . $keyField][$indexField][$field] = $valueField;

                            if(array_search($keyField, $tmpTemplateVarMIGX) === false){
                                $tmpTemplateVarMIGX[] = $keyField;
                            }
                        }

                        break;

                    case ('tv'):

                        if ($imagesPath) {
                            $row[$index] = $imagesPath . $row[$index];
                        }

                        $tmpRecord[$type]['tv' . $key] = $row[$index];

                        break;

                    case ('base'):

                        if ($imagesPath) {
                            $row[$index] = $imagesPath . $row[$index];
                        }

                        $tmpRecord[$type][$key] = $row[$index];

                        break;
                }

                if ($key == $keyUniqueField) {
                    $tmpRecord['uniqueField'][$key] = $row[$index];
                }
            }

            if (!empty($tmpRecord)) {

                foreach ($this->params['default'] as $keyParam => $param) {

                    switch ($keyParam) {
                        case 'base':
                            $tmpRecord['base'] = $tmpRecord['base'] + $param;
                            break;
                        case 'tv':
                            foreach ($param as $keyField => $field){

                                $tmpRecord['tv']['tv'.$keyField] = $field;
                            }
                            break;
                        case 'migx':
                            foreach ($param as $keyValues => $values){

                                foreach ($values as $keyValue => $value){
                                    $this->separatorMigx($value);

                                    for($i = 0, $count = count($value); $i < $count; $i++){

                                        if(array_search($keyValues, $tmpTemplateVarMIGX) === false){
                                            $tmpTemplateVarMIGX[] = $keyValues;
                                        }

                                        $tmpRecord['migx']['tv'.$keyValues][$i][$keyValue] = $value[$i];
                                    }
                                }

                            }

                            break;
                    }

                }
                $records[] = $tmpRecord;
            }

        }

        unset($this->rows);

        $this->migxConfigs = $this->getMigxConfigs($tmpTemplateVarMIGX);

        return $records;
    }

    /**
     *
     *
     * @return array/array
     */
    protected function getRelatedParam($key, &$data)
    {
        $keyUniqueField = $this->params['uniqueField'];
        $imagesPath = (isset($this->params['imagesParams'][$keyUniqueField])) ? $this->params['imagesParams'][$keyUniqueField] : null;
        $separator = $this->params['relatedParams'][$key];

        $type = null;

        if (preg_match('/^(\d+).([\w.]+)$/', $keyUniqueField)) {
            $type = 'migx';
        } else {
            $type = 'tv';
        }

        switch ($type) {
            case('migx'):

                preg_match_all("/\(([^()]*+|(?R))*\)/", $data, $matches);
                $data = $matches[0];

                foreach ($data as &$param) {

                    $param = trim($param, "()");
                    $param = explode($separator, $param);

                    if ($imagesPath) {
                        foreach ($param as &$value) {

                            $value = explode($this->params['separator'], $value);

                            foreach ($value as &$val) {

                                $val = $imagesPath . $val;
                            }
                        }
                    }
                }

                break;

            case('tv'):

                $data = explode($separator, $data);

                if ($imagesPath) {
                    foreach ($data as &$value) {

                        $value = $imagesPath . $value;
                    }
                }

                break;
        }

        return $data;
    }

    /**
     *
     * @param array &$tmpRecord
     * @return array/string
     */
    protected function separatorMigx(&$record)
    {
        $separator = $this->params['separator'];

        $record = explode($separator, $record);
    }

    /**
     *
     * @return boolean
     */
    protected function importResources()
    {
        $mode = $this->getProperty('mode');

        switch ($mode) {
            case ('insert'):

                foreach ($this->records as &$record) {
                    $this->insertResource($record);
                }

                $this->setRelatedResources();

                break;

            case ('update'):

                $this->updateResources();

                $this->setRelatedResources();

                break;

            case ('delete'):

                return $this->deleteResources();

                break;
        }

        return true;
    }

    /**
     *
     * @return boolean
     */
    protected function insertResource(&$record)
    {
        if (!isset($record['base']['pagetitle'])) {
            $this->modx->log(MODX::LOG_LEVEL_ERROR, 'ImportDocs: ' . $this->modx->lexicon('im.docs_error_pagetitle'));
        }

        if (!isset($record['base']['alias'])) {
            $record['base']['alias'] = $this->makeAlias($record['base']['pagetitle']);
        }

        $record['base']['alias'] = $this->getUniqueAlias($record['base']['alias']);
        $record['tv']['tvs'] = 1;
        $record['migx'] = $this->setMIGXFields($record['migx']);

        $createObject = $this->modx->runProcessor('resource/create', $record['base'] + $record['tv'] + $record['migx']);

        if ($createObject->isError()) {
            $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'ImportDocs: ' . $createObject->getMessage());
            return false;
        }

        $object = $createObject->getObject();

        $idResource = $object['id'];

        if (!$idResource) return false;

        $record['base']['id'] = $idResource;

        return true;
    }

    /**
     *
     * @return boolean
     */
    protected function updateResources()
    {
        $keyUniqueField = $this->params['uniqueField'];
        $modeInsertUpdate = $this->getProperty('insert-update-mode');
        $tmpFieldsResource = $this->getAllTypeUniqueField();

        foreach ($this->records as &$record) {

            $record['base']['id'] = array_search($record['uniqueField'][$keyUniqueField], $tmpFieldsResource);


            if (!$record['base']['id']) {

                if ($modeInsertUpdate === 'on') {
                    $this->insertResource($record);
                    continue;
                } else {
                    $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'ImportDocs: ' . 'Ресурс ' . $record['uniqueField'] . ' не найден');
                }

            }
            $record['migx'] = $this->setMIGXFields($record['migx']);

            $record['tv']['tvs'] = 1;

            $createObject = $this->modx->runProcessor('resource/update', $record['base'] + $record['tv'] + $record['migx']);

            if ($createObject->isError()) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'ImportDocs: ' . $createObject->getMessage());
            }
        }

        return true;
    }

    /**
     *
     * @return Iterator
     */
    protected function getAllTypeUniqueField()
    {
        $keyUniqueField = $this->params['uniqueField'];
        $tmpFieldsResource = [];

        //если поле относится к типу MIGX
        if (preg_match('/^(\d+).([\w.]+)$/', $keyUniqueField, $arrayPartsField)) {

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

        } else if (is_numeric($keyUniqueField)) {

            $query = $this->modx->newQuery('modTemplateVarResource');
            $query->where(['tmplvarid' => $keyUniqueField]);
            $query->select(['contentid', 'value']);

            $tvs = $this->modx->getIterator('modTemplateVarResource', $query);

            if (!$tvs) return $tmpFieldsResource;

            foreach ($tvs as $tv) {
                $tmpFieldsResource[$tv->get('contentid')] = $tv->get('value');
            }

        } else {

            $query = $this->modx->newQuery('modResource');
            $query->select(['id', $keyUniqueField]);

            $fields = $this->modx->getIterator('modResource', $query);

            if (!$fields) return $tmpFieldsResource;

            foreach ($fields as $field) {
                $tmpFieldsResource[$field->get('id')] = $field->get($keyUniqueField);
            }
        }

        return $tmpFieldsResource;
    }

    /**
     *
     * @return boolean
     */
    protected function deleteResources()
    {
        $keyUniqueField = $this->params['uniqueField'];
        $tmpFieldsResource = $this->getAllTypeUniqueField();

        foreach ($this->records as $record) {
            if (isset($record['uniqueField'])) {
                $idResource = array_search($record['uniqueField'][$keyUniqueField], $tmpFieldsResource);

                if ($idResource !== false) {

                    $deleteObject = $this->modx->runProcessor('resource/delete', ['id' => $idResource]);

                    if ($deleteObject->isError()) {
                        $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'ImportDocs: ' . $deleteObject->getMessage());
                    }
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
    protected function makeAlias($pagetitle)
    {
        $alias = "";

        if (!empty($this->translit) && is_array($this->translit)) {
            $alias = strtr($pagetitle, $this->translit);
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
    protected function getUniqueAlias($alias)
    {
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

    /*
     *
     * @return array/json
     */
    protected function setMIGXFields($migxData)
    {
        foreach ($migxData as $keyField => &$field){

            $configFields = $this->migxConfigs[$keyField];

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

        return $migxData;
    }

    /**
     *
     * @return boolean
     */
    protected function setRelatedResources()
    {
        $tmpFieldsResource = $this->getAllTypeUniqueField();

        foreach ($this->records as &$record) {
            if (isset($record['related'])) {

                foreach ($record['related'] as $keyFields => $param) {

                    $tmpRelatedResourceList = [];

                    if(preg_match('/^(\d+).([\w.]+)$/', $keyFields, $arrayPartsField)){

                        foreach ($param as $indexParam => $value){

                            foreach ($value as $indexValue => $v) {

                                $idResourceRelated = array_search($v, $tmpFieldsResource);

                                $tmpRelatedResourceList[$indexParam][$indexValue] = (string)$idResourceRelated;
                            }

                        }

                        $tv = $this->modx->fromJSON($record['migx']['tv'.$arrayPartsField[1]]);

                        foreach ($tv as $indexField => &$field){
                            if(isset($tmpRelatedResourceList[$indexField])) {
                                $field[$arrayPartsField[2]] = implode('||', $tmpRelatedResourceList[$indexField]);
                            }
                        }

                        $record['tv']['tv' .$arrayPartsField[1]] = $this->modx->toJSON($tv);

                    }

                    foreach ($param as $fields) {

                        $idResourceRelated = array_search($fields, $tmpFieldsResource);

                        if ($idResourceRelated !== false) {
                            $tmpRelatedResourceList['tv' . $keyFields][] = $idResourceRelated;
                        }


                    }

                    if (empty($tmpRelatedResourceList)) continue;


                    $createObject = $this->modx->runProcessor('resource/update', $record['base'] + $record['tv']);

                    if ($createObject->isError()) {
                        $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'ImportDocs: ' . $createObject->getMessage());
                        exit(json_encode($createObject->getMessage()));
                    }
                }
            }
        }

        return true;
    }
}


return 'ImportDocsProcessor';