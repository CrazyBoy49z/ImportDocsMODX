<?php

class ImportDocsSaveParamsProcessor extends modProcessor
{
    public $language_topics = ['importdocs'];
    // private $params_file_path = MODX_CORE_PATH . 'components/importdocs/model/params.json';
    //private $params_file_path = $this->modx->getOption('im.docs_core_path', null, $modx->getOption('core_path') . 'components/importdocs/');
    //public $permission = 'save';


    /**
     * @return array|string
     */
    public function process()
    {

        $type = $this->getProperty('type');
        $data = $this->getProperty('data');
        $uniqueField = $this->getProperty('uniqueField');
        $separator = $this->getProperty('separator');
        $data = $this->modx->fromJSON($data, true);

        array_walk($data, function (&$param) {
            $param['consolation'] = mb_strtolower(trim($param['consolation']));
        });

        $params_file_path = $this->modx->getOption('importdocs_core_path', null) . 'model/params.json';

        $params_file = file_get_contents($params_file_path);

        if ($params_file === false) {
            $this->modx->log(MODX::LOG_LEVEL_ERROR, 'ImportDocs: Parameter file not found.');
            exit ($this->modx->toJSON(['success' => 0, 'message' => 'Parameter file not found.']));
        }

        $params_tmp = $this->modx->fromJSON($params_file);

        switch ($type) {
            case 'baseFields':
                $params_tmp['base_fields'] = $data;
                break;
            case 'tvFields':
                $params_tmp['tv_fields'] = $data;
                break;
            case 'migxFields':
                $params_tmp['migx_fields'] = $data;
                break;
            case 'baseParams':
                foreach ($data as $value) {
                    switch ($value['type']) {
                        case 'imagesParams':
                            $params_tmp['imagesParams'] = $value['data'];
                            break;
                        case 'relatedParams':
                            $params_tmp['relatedParams'] = $value['data'];
                            break;
                        case 'otherParams':
                            $params_tmp['otherParams'] = $value['data'];
                            break;
                    }
                }

                $params_tmp['uniqueField'] = $uniqueField;
                $params_tmp['separator'] = $separator;
                break;
            default:
                $this->modx->log(modX::LOG_LEVEL_ERROR, 'ImportDocs: field type (' . $type . ') not supported');
                return ['success' => false, 'message' => 'field type not supported'];
                break;
        }

        $this->removeMissingParameters(array_merge($params_tmp['base_fields'],$params_tmp['tv_fields'],$params_tmp['migx_fields']), $params_tmp['imagesParams']);
        $this->removeMissingParameters(array_merge($params_tmp['base_fields'],$params_tmp['tv_fields'],$params_tmp['migx_fields']), $params_tmp['relatedParams']);

        file_put_contents($params_file_path, $this->modx->toJSON($params_tmp));

        unset($params_file);

        return ['success' => true, 'message' => 'Parameters successfully saved!'];
    }



    protected function removeMissingParameters($params, &$checkParams)
    {

        foreach ($checkParams as $key => $value) {

            if (array_search($value['id'], array_column($params, 'id')) === false) {
                unset($checkParams[$key]);
            }
        }
    }
}

return 'ImportDocsSaveParamsProcessor';