<?php

namespace Crocodicstudio\Crudbooster\Controllers\CBController;

use Crocodicstudio\Crudbooster\CBCoreModule\RelationHandler;
use Crocodicstudio\Crudbooster\Controllers\FormValidator;
use Crocodicstudio\Crudbooster\Helpers\CRUDBooster;
use Crocodicstudio\Crudbooster\Helpers\DbInspector;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;

trait FormSubmitHandlers
{
    public $arr = [];

    public $return_url = null;

    public function inputAssignment($id = null)
    {
        $hide_form = (request('hide_form')) ? unserialize(request('hide_form')) : [];

        foreach ($this->form as $form) {
            $name = $form['name'];
            $type = $form['type'] ?: 'text';
            $inputdata = request($name);

            if (! $name || in_array($name, $hide_form) || $form['exception']) {
                continue;
            }

            $hookPath = CbComponentsPath($type).DIRECTORY_SEPARATOR.'hookInputAssignment.php';
            if (file_exists($hookPath)) {
                require_once($hookPath);
            }
            unset($hookPath);

            if (Request::hasFile($name)) {
                continue;
            }

            if ($inputdata == '' && DbInspector::isNullableColumn($this->table, $name)) {
                continue;
            }

            $this->arr[$name] = '';

            if ($inputdata != '') {
                $this->arr[$name] = $inputdata;
            }
        }
    }

    public function postAddSave()
    {
        $this->genericLoader();

        app(FormValidator::class)->validate(null, $this->form, $this);
        $this->inputAssignment();

        $this->setTimeStamps('created_at');

        $this->hookBeforeAdd($this->arr);
        $id = (int) $this->table()->insertGetId($this->arr);
        app(RelationHandler::class)->save($this->table, $id, $this->data_inputan);
        $this->hookAfterAdd($id);

        event('cb.dataInserted', [$this->table, $id, YmdHis(), cbUser()]);
        $this->sendResponseForSave('alert_add_data_success');
    }

    public function postEditSave($id)
    {
        $id = (int) $id;
        $this->genericLoader();

        app(FormValidator::class)->validate($id, $this->form, $this);
        $this->inputAssignment($id);

        $this->setTimeStamps('updated_at');

        $this->hookBeforeEdit($this->arr, $id);
        $this->findRow($id)->update($this->arr);
        app(RelationHandler::class)->save($this->table, $id, $this->data_inputan);
        $this->hookAfterEdit($id);

        event('cb.dataUpdated', [$this->table, $id, YmdHis(), cbUser()]);

        $this->sendResponseForSave('alert_update_data_success');
    }

    private function sendResponseForSave($msg)
    {
        $this->return_url = $this->return_url ?: request('return_url');
        if ($this->return_url) {
            if (request('submit') == cbTrans('button_save_more')) {
                backWithMsg(cbTrans($msg), 'success');
            }
            CRUDBooster::redirect($this->return_url, cbTrans($msg), 'success');
        }
        if (request('submit') == cbTrans('button_save_more')) {
            CRUDBooster::redirect(CRUDBooster::mainpath('add'), cbTrans($msg), 'success');
        }
        CRUDBooster::redirect(CRUDBooster::mainpath(), cbTrans($msg), 'success');
    }

    private function setTimeStamps($col)
    {
        if (Schema::hasColumn($this->table, $col)) {
            $this->arr[$col] = YmdHis();
        }
    }
}