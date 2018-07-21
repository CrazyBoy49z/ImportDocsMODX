<?php

class ImportDocsBaseParamsProcessor extends modProcessor
{
    public $languageTopics = ['importdocs'];
    //public $permission = 'save';


    /**
     * @return array|string
     */
    public function process()
    {
        $type = $this->getProperty('type');

        $params_file_path = $this->modx->getOption('importdocs_core_path', null) . 'model/params.json';
        $params_tmp = file_get_contents($params_file_path);

        $params_file = file_get_contents($params_file_path);

        if($params_file === false) {
            $this->modx->log(MODX::LOG_LEVEL_ERROR, 'ImportDocs: Parameter file not found.');
            exit ($this->modx->toJSON(['success' => 0, 'message' => 'Parameter file not found.']));
        }

        $params_tmp_array = $this->modx->fromJSON($params_tmp, true);

        $results = [];

        switch ($type) {
            case 'baseFields':
                $results = $params_tmp_array['base_fields'];
                break;
            case 'tvFields':
                $results = $params_tmp_array['tv_fields'];
                break;
            case 'tvParams':
                $results =  $this->_getAllParams($params_tmp_array['tv_fields'], $params_tmp_array['migx_fields']);;
                break;
            case 'migxFields':
                $results = $params_tmp_array['migx_fields'];
                break;
            case 'allParams':
                $results = $this->_getAllParams($params_tmp_array['base_fields'], $params_tmp_array['tv_fields'], $params_tmp_array['migx_fields']);
                break;
            case 'imagesParams':
                $results = $params_tmp_array['imagesParams'];
                break;
            case 'relatedParams':
                $results = $params_tmp_array['relatedParams'];
                break;
            case 'otherParams':
                $results = $params_tmp_array['otherParams'];
                break;
            case 'getParams';
                $results = ['uniqueField' => $params_tmp_array['uniqueField'], 'separator' => $params_tmp_array['separator']];
                break;
            default:
                $this->modx->log(modX::LOG_LEVEL_ERROR, 'ImportDocs: field type (' . $type . ') not supported');
                return ['success' => false, 'message' => 'field type not supported'];
                break;
        }

        return $this->modx->toJSON(['success' => 1, 'results' => $results]);
    }


    private function _getAllParams(){

        $args_param = func_get_args();

        $tmp_array_param = [];

        foreach ($args_param as $key){
            foreach ($key as $value) {
                array_push($tmp_array_param, [$value['id'], $value['consolation']]);
            }
        }

        return $tmp_array_param;

    }
}

return 'ImportDocsBaseParamsProcessor';