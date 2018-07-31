<?php

namespace Crocodicstudio\Crudbooster\Modules\SettingModule;

class SettingsForm
{
    public static function makeForm($value)
    {
        $form = [
            [
                'label' => 'Group',
                'name' => 'group_setting',
                'value' => $value
            ],
            [
                'label' => 'Label',
                'name' => 'label'
            ],
            [
                'label' => 'type',
                'name' => "content_input_type",
                'type' => "select_dataenum",
                'options' => ["enum" => ["text", "number", "email", "textarea", "wysiwyg", "upload_image", "upload_document", "datepicker", "radio", "select"]],
            ],
            [
                'label' => "Radio / Select Data",
                'name' => 'dataenum',
                "placeholder" => "Example : abc,def,ghi",
                "jquery" => "function show_radio_data() { var cit = $('#content_input_type').val(); if(cit == 'radio' || cit == 'select') { $('#form-group-dataenum').show(); }else{ $('#form-group-dataenum').hide(); } } $('#content_input_type').change(show_radio_data); show_radio_data(); ",
            ],
            [
                'label' => "Helper Text",
                'name' => "helper",
                'type' => "text"
            ],
        ];

        return $form;
    }
}