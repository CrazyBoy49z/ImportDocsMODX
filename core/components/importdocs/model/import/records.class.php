<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

class Records
{

    /** @var modX $modx */
    public $modx;

    /** @var array params */
    private $params = [];

    /** @var array $indices */
    private $indices = [];

    /** @var string $typeUniqueField */
    private $typeUniqueField;

    /** @var string $templateVarMIGX */
    public $templateVarMIGX = [];

    /** @var array $records */
    public $records = [];


    function __construct(modX &$modx, array $params = [], array $rowHeaders = [], array $rows = [])
    {
        $this->modx =& $modx;
        $this->params = $params;

        $this->setTypeUniqueField();
        $this->indices = $this->getColumnIndexes($rowHeaders);
        $this->records = $this->getArrayRecords($rows);
    }


    public function getKeyUniqueField(){
        return $this->params['uniqueField'];
    }

    public function getTypeUniqueField(){
        return $this->typeUniqueField;
    }

    private function setTypeUniqueField(){
        if (preg_match('/^(\d+).([\w.]+)$/', $this->params['uniqueField'])) {
            $this->typeUniqueField = 'migx';
        } else if (is_numeric($this->params['uniqueField'])) {
            $this->typeUniqueField = 'tv';
        } else {
            $this->typeUniqueField = 'base';
        };
    }


    /**
     * @param array $rowHeaders
     * @return array
     */
    protected function getColumnIndexes($rowHeaders)
    {
        $indices = [];

        foreach ($rowHeaders as $row) {
            $key = null;
            $type = null;

            if ($key = array_search($row, $this->params['migx_fields'])) {
                $type = 'migx';
            } else if ($key = array_search($row, $this->params['tv_fields'])) {
                $type = 'tv';
            } else if ($key = array_search($row, $this->params['base_fields'])) {
                $type = 'base';
            }

            $indices[] = [$key => $type];

        }

        return $indices;
    }


    /**
     * @param array $rows
     * @return array
     */
    protected function getArrayRecords($rows)
    {
        $records = [];

        $keyUniqueField = $this->params['uniqueField'];

        foreach ($rows as $row) {

            $record = [];

            foreach ($this->indices as $index => $indexValue) {

                if (!$row[$index] || !$indexValue) continue;

                $key = key($indexValue);
                $type = $indexValue[$key];

                if (isset($this->params['relatedParams'][$key])) {
                    $record['related'][$key] = $this->setRelatedField($key, $row[$index]);
                    continue;
                }

                $imagesPath = (isset($this->params['imagesParams'][$key])) ? $this->params['imagesParams'][$key] : null;

                switch ($type) {
                    case ('migx'):
                        preg_match('/^(\d+).([\w.]+)$/', $key, $arrayPartsField);

                        $keyField = $arrayPartsField[1];
                        $field = $arrayPartsField[2];
                        $this->splitMIIGField($row[$index]);

                        foreach ($row[$index] as $indexField => &$valueField) {

                            if ($imagesPath) {
                                $valueField = $imagesPath . trim($valueField);
                            }

                            $record[$type]['tv' . $keyField][$indexField][$field] = trim($valueField);

                            if (array_search($keyField, $this->templateVarMIGX) === false) {
                                $this->templateVarMIGX[] = $keyField;
                            }
                        }
                        break;
                    case ('tv'):
                        if ($imagesPath) {
                            $row[$index] = $imagesPath . trim($row[$index]);
                        }
                        $record[$type]['tv' . $key] = trim($row[$index]);
                        break;
                    case ('base'):
                        $record[$type][$key] = trim($row[$index]);
                        break;
                }

                if ($key == $keyUniqueField) {
                    $record['uniqueField'][$key] = $row[$index];
                }
            }

            if (!empty($record)) {
                foreach ($this->params['default'] as $keyParam => $param) {

                    switch ($keyParam) {
                        case 'base':
                            $record['base'] = $record['base'] + $param;
                            break;
                        case 'tv':
                            foreach ($param as $keyField => $field) {
                                $record['tv']['tv' . $keyField] = $field;
                            }
                            break;
                        case 'migx':
                            foreach ($param as $keyValues => $values) {
                                foreach ($values as $keyValue => $value) {
                                    $this->splitMIIGField($value);
                                    for ($i = 0, $count = count($value); $i < $count; $i++) {
                                        if (array_search($keyValues, $this->templateVarMIGX) === false) {
                                            $this->templateVarMIGX[] = $keyValues;
                                        }
                                        $record['migx']['tv' . $keyValues][$i][$keyValue] = $value[$i];
                                    }
                                }
                            }
                            break;
                    }
                }
                $records[] = $record;
            }

        }

        unset($this->rows);

        return $records;

    }


    /**
     * @param string $keyField
     * @param string $valueField
     * @return array
     */
    protected function setRelatedField($keyField, $valueField)
    {
        $keyUniqueField = $this->params['uniqueField'];
        $imagesPath = (isset($this->params['imagesParams'][$keyUniqueField])) ? $this->params['imagesParams'][$keyUniqueField] : null;
        $separator = $this->params['relatedParams'][$keyField];

        $type = null;

        if (preg_match('/^(\d+).([\w.]+)$/', $keyField)) {
            $typeField = 'migx';
        } else {
            $typeField = 'tv';
        }

        switch ($typeField) {
            case('migx'):

                if (array_search($keyField, $this->templateVarMIGX) === false) {
                    $this->templateVarMIGX[] = $keyField;
                }

                preg_match_all("/\(([^()]*+|(?R))*\)/", $valueField, $matches);
                $valueField = $matches[0];
                foreach ($valueField as &$value) {
                    $value = trim($value, "()");
                    $value = explode($separator, $value);

                    foreach ($value as &$item) {
                        if ($this->typeUniqueField === 'migx') {
                            $item = explode($this->params['separator'], $item);
                            if ($imagesPath) {
                                $item = array_map(function ($value) use ($imagesPath) {
                                    return $imagesPath . trim($value);
                                }, $item);
                            } else {
                                $item = array_map('trim', $item);
                            }
                        }else{
                            $item = trim($item);
                            if ($imagesPath) {
                                $item = $imagesPath . $item;
                            }
                        }
                    }
                }
                break;
            case('tv'):
                $valueField = explode($separator, $valueField);

                if ($this->typeUniqueField === 'migx') {
                    foreach ($valueField as &$value){
                        $value = explode($this->params['separator'], $value);
                        if($imagesPath){
                            $value = array_map(function ($value) use ($imagesPath) {
                                return $imagesPath . trim($value);
                            }, $value);
                        }else{
                            $value = array_map('trim', $value);
                        }
                    }

                }else{
                    $valueField = array_map('trim', $valueField);
                }
                break;
        }

        return $valueField;
    }


    /**
     *
     * @param string &$MIGXField
     * @return boolean
     */
    protected function splitMIIGField(&$MIGXField)
    {
        $separator = $this->params['separator'];
        $MIGXField = explode($separator, $MIGXField);

        return true;
    }


}