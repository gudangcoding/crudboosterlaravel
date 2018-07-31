<?php
/**
 * @param $formInput
 * @return array
 */
$queryData = function ($formInput)
{
    if (! $formInput['options']['table']) return;
    $field_label = $formInput['options']['field_label'];
    $field_value = $formInput['options']['field_value'];

    $selects_data = DB::table($formInput['options']['table']);

    if (\Schema::hasColumn($formInput['options']['table'], 'deleted_at')) {
        $selects_data->where('deleted_at', NULL);
    }

    if (@$formInput['options']['sql_where']) {
        $selects_data->whereRaw($formInput['options']['sql_where']);
    }

    $selects_data->addselect($field_label);
    $selects_data->addselect($field_value);
    if ($formInput['options']['sql_orderby']) {
        $selects_data->orderByRaw($formInput['options']['sql_orderby']);
    } else {
        $selects_data->orderby($field_value, 'desc');
    }
    $selects_data = $selects_data->get();
    return [$selects_data, $field_label, $field_value];
};

$data = $queryData($form);

$selects_data = $data[0];
$field_label = $data[1];
$field_value = $data[2];

?>

<div class='form-group {{$header_group_class}} {{ ($errors->first($name))?"has-error":"" }}' id='form-group-{{$name}}'
     style="{{@$formInput['style']}}">
    <label class='control-label col-sm-2'>{{$label}} {!!($required)?"<span class='text-danger' title='".cbTrans('this_field_is_required')."'>*</span>":"" !!}</label>
    <div class="{{$col_width?:'col-sm-10'}}">

        @if (@$formInput['options']['table'])
            @foreach ($selects_data as $data)
                <div data-val='{!! $val !!}' class='checkbox  {{$disabled}}'>
                    <label>
                        <input type='checkbox'
                        {{ $disabled }}
                        {{ is_checked($formInput['options']['result_format'], $value, $data->field_value) }}
                        name='{!! $name !!}[]'
                        value='{!! $data->$field_value !!}'>
                        {!! $data->$field_label !!}
                    </label>
                </div>
            @endforeach
        @endif
        {!! underField($formInput['help'], $errors->first($name)) !!}
    </div>
</div>