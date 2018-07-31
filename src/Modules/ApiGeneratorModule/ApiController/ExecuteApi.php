<?php

namespace Crocodicstudio\Crudbooster\Modules\ApiGeneratorModule\ApiController;

use Crocodicstudio\Crudbooster\Helpers\DbInspector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class ExecuteApi
{
    private $ctrl;

    /**
     * ExecuteApi constructor.
     *
     * @param $ctrl
     */
    public function __construct($ctrl)
    {
        $this->ctrl = $ctrl;
    }

    public function execute()
    {
        $rowApi = DB::table('cms_apicustom')->where('permalink', $this->ctrl->permalink)->first();
        ApiValidations::doValidations($rowApi, $this->ctrl);

        $table = $rowApi->tabel;

        @$parameters = unserialize($rowApi->parameters);
        list($type_except, $input_validator) = $this->validateParams($parameters, $table);

        $posts = request()->all();
        $this->ctrl->hookBefore($posts);


        unset($posts['limit'], $posts['offset'], $posts['orderby']);
        $actionType = $rowApi->aksi;
        if (in_array($actionType, ['list', 'detail', 'delete'])) {
            @$responses = unserialize($rowApi->responses);
            $responses_fields = $this->prepareResponses($responses);
            $data = $this->fetchDataFromDB($table, $responses, $responses_fields, $parameters, $posts);

            $this->filterRows($data, $parameters, $posts, $table, $type_except);

            if (!is_null($rowApi->sql_where)) {
                $data->whereraw($rowApi->sql_where);
            }

            $this->ctrl->hookQuery($data);
            $result = [];
            if ($actionType == 'list') {
                $result = HandleListAction::handle($table, $data, $responses_fields, $this);
            } elseif ($actionType == 'detail') {
                $result = HandleDetailsAction::handle($data, $parameters, $posts, $responses_fields, $this);
            } elseif ($actionType == 'delete') {
                $result = $this->handleDeleteAction($table, $data);
            }
            ApiResponder::send($result, $posts, $this->ctrl);
        } elseif (in_array($actionType, ['save_add', 'save_edit'])) {
            $rowAssign = array_filter($input_validator, function ($column) use ($table) {
                return Schema::hasColumn($table, $column);
            }, ARRAY_FILTER_USE_KEY);

            $this->handleAddEdit($parameters, $posts, $rowAssign);
        }

    }

    /**
     * @param $responses
     * @return array
     */
    private function prepareResponses($responses)
    {
        $responsesFields = [];
        foreach ($responses as $r) {
            if ($r['used']) {
                $responsesFields[] = $r['name'];
            }
        }

        return $responsesFields;
    }

    /**
     * @param $table
     * @param $data
     * @return mixed
     */
    private function handleDeleteAction($table, $data)
    {
        if (\Schema::hasColumn($table, 'deleted_at')) {
            $delete = $data->update(['deleted_at' => YmdHis()]);
        } else {
            $delete = $data->delete();
        }

        $status = ($delete) ? 1 : 0;
        $msg = ($delete) ? "success" : "failed";

        return ApiResponder::makeResult($status, $msg);
    }

    /**
     * @param $data
     * @param $parameters
     * @param $posts
     * @param $table
     * @param $typeExcept
     */
    private function filterRows($data, $parameters, $posts, $table, $typeExcept)
    {
        $data->where(function ($w) use ($parameters, $posts, $table, $typeExcept) {
            foreach ($parameters as $param) {
                $name = $param['name'];
                $type = $param['type'];
                $value = $posts[$name];
                $used = $param['used'];
                $required = $param['required'];

                if (in_array($type, $typeExcept)) {
                    continue;
                }

                if ($param['config'] != '' && substr($param['config'], 0, 1) != '*') {
                    $value = $param['config'];
                }

                if ($required == '1') {
                    $this->applyWhere($w, $table, $name, $value);
                } else {
                    if ($used && $value) {
                        $this->applyWhere($w, $table, $name, $value);
                    }
                }
            }
        });
    }

    /**
     * @param $w
     * @param $table
     * @param $name
     * @param $value
     */
    private function applyWhere($w, $table, $name, $value)
    {
        if (\Schema::hasColumn($table, $name)) {
            $w->where($table.'.'.$name, $value);
        } else {
            $w->having($name, '=', $value);
        }
    }

    /**
     * @param $parameters
     * @param $posts
     * @param $data
     * @param $table
     * @return null
     */
    private function params($parameters, $posts, $data, $table)
    {
        foreach ($parameters as $param) {
            $name = $param['name'];
            $type = $param['type'];
            $value = $posts[$name];
            $used = $param['used'];
            $required = $param['required'];
            $config = $param['config'];

            if ($type == 'password') {
                $data->addselect($table.'.'.$name);
            }

            if ($type !== 'search') {
                continue;
            }
            $search_in = explode(',', $config);

            if ($required == '1' || ($used && $value)) {
                $this->applyLike($data, $search_in, $value);
            }
        }
    }

    /**
     * @param $parameters
     * @param $posts
     * @param $rowAssign
     */
    private function handleAddEdit($parameters, $posts, $rowAssign)
    {
        foreach ($parameters as $param) {
            $name = $param['name'];
            $used = $param['used'];
            $value = $posts[$name];
            if ($used == '1' && $value == '') {
                unset($rowAssign[$name]);
            }
        }
    }

    /**
     * @param $table
     * @param $data
     * @param $responses
     *
     * @param $responsesFields
     * @return array
     */
    private function responses($table, $data, $responses, $responsesFields)
    {
        $name_tmp = [];

        $responses = $this->filterRedundantResp($responses);

        foreach ($responses as $resp) {
            $name = $resp['name'];
            $subquery = $resp['subquery'];
            $used = intval($resp['used']);

            if (in_array($name, $name_tmp)) {
                continue;
            }

            if ($subquery) {
                $data->addSelect(DB::raw('('.$subquery.') as '.$name));
                $name_tmp[] = $name;
                continue;
            }

            if ($used) {
                $data->addSelect($table.'.'.$name);
            }

            $name_tmp[] = $name;
            $name_tmp = $this->joinRelatedTables($table, $responsesFields, $name, $data, $name_tmp);
        }

        return $data;
    }

    /**
     * @param $data
     * @param $search_in
     * @param $value
     */
    private function applyLike($data, $search_in, $value)
    {
        $data->where(function ($w) use ($search_in, $value) {
            foreach ($search_in as $k => $field) {
                $method = 'orWhere';
                if ($k == 0) {
                    $method = 'where';
                }
                $w->$method($field, "like", "%$value%");
            }
        });
    }

    /**
     * @param $table
     * @param $responsesFields
     * @param $name
     * @param $data
     * @param $nameTmp
     * @return array
     */
    private function joinRelatedTables($table, $responsesFields, $name, $data, $nameTmp)
    {
        if (! DbInspector::isForeignKey($name)) {
            return $nameTmp;
        }
        $joinTable = DbInspector::getRelatedTableName($name);
        $data->leftjoin($joinTable, $joinTable.'.id', '=', $table.'.'.$name);
        foreach (\Schema::getColumnListing($joinTable) as $jf) {
            $jfAlias = $joinTable.'_'.$jf;
            if (in_array($jfAlias, $responsesFields)) {
                $data->addselect($joinTable.'.'.$jf.' as '.$jfAlias);
                $nameTmp[] = $jfAlias;
            }
        }

        return $nameTmp;
    }

    /**
     * @param $inputValidator
     * @param $dataValidation
     * @param $posts
     * @return mixed
     */
    private function doValidation($inputValidator, $dataValidation, $posts)
    {
        $validator = Validator::make($inputValidator, $dataValidation);
        if (! $validator->fails()) {
            return true;
        }
        $message = $validator->errors()->all();
        $message = implode(', ', $message);
        $result = ApiResponder::makeResult(0, $message);

        ApiResponder::send($result, $posts, $this->ctrl);
    }

    /**
     * @param $responses
     * @return array
     */
    private function filterRedundantResp($responses)
    {
        $responses = array_filter($responses, function ($resp) {
            return ! ($resp['name'] == 'ref_id' || $resp['type'] == 'custom');
        });

        $responses = array_filter($responses, function ($resp) {
            return (intval($resp['used']) != 0 || DbInspector::isForeignKey($resp['name']));
        });

        return $responses;
    }

    /**
     * @param $parameters
     * @param $table
     * @return array
     */
    private function validateParams($parameters, $table)
    {
        $posts = request()->all();
        if (! $parameters) {
            return ['', ''];
        }
        $typeExcept = ['password', 'ref', 'base64_file', 'custom', 'search'];
        $inputValidator = [];
        $dataValidation = [];

        $parameters = array_filter($parameters, function ($param){
            return !(is_string($param['config'])&& !starts_with($param['config'], '*'));
        });

        foreach ($parameters as $param) {
            $name = $param['name'];
            $value = $posts[$name];
            $used = $param['used'];

            if ($used == 0) {
                continue;
            }

            $inputValidator[$name] = $value;
            $dataValidation[$name] = app(ValidationRules::class)->make($param, $typeExcept, $table);
        }

        $this->doValidation($inputValidator, $dataValidation, $posts);

        return [$typeExcept, $inputValidator];
    }

    /**
     * @param $table
     * @param $responses
     * @param $responsesFields
     * @param $parameters
     * @param $posts
     * @return array
     */
    private function fetchDataFromDB($table, $responses, $responsesFields, $parameters, $posts)
    {
        $data = DB::table($table);
        $data->skip(request('offset', 0));
        $data->take(request('limit', 20));
        $data = $this->responses($table, $data, $responses, $responsesFields); //End Responses

        $this->params($parameters, $posts, $data, $table);

        if (\Schema::hasColumn($table, 'deleted_at')) {
            $data->where($table.'.deleted_at', null);
        }

        return $data;
    }
}