<form method='post' id="form" enctype="multipart/form-data" action='{{$action}}'>
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <div class="box-body table-responsive no-padding">
        <div class='callout callout-info'>
            * Just ignoring the column where you are not sure the data is suit with the column or not.<br/>
            * Warning !, Unfortunately at this time, the system can't import column that contains image or
            photo url.
        </div>
        @push('head')
            <style type="text/css">
                th, td {
                    white-space: nowrap;
                }
            </style>
        @endpush
        <table class='table table-bordered' style="width:130%">
            <thead>
            <tr class='success'>
                @foreach($table_columns as $k => $column)
                    @if (\Crocodicstudio\Crudbooster\Modules\ModuleGenerator\ControllerGenerator\FieldDetector::isExceptional($column))
                        @continue
                    @endif
                    <?php
                    $help = '';
                    if (starts_with($column, 'id_')) {
                        $relational_table = str_after($column, 'id_');
                        $help = "<a href='#' title='This is foreign key, so the System will be inserting new data to table `$relational_table` if doesn`t exists'><strong>(?)</strong></a>";
                    }
                    ?>
                    <th data-no-column='{{$k}}'>{{ $column }} {!! $help !!}</th>
                @endforeach
            </tr>
            </thead>
            <tbody>

            <tr>
                @foreach($table_columns as $k => $column)
                    @if (\Crocodicstudio\Crudbooster\Modules\ModuleGenerator\ControllerGenerator\FieldDetector::isExceptional($column))
                        @continue
                    @endif
                    <td data-no-column='{{$k}}'>
                        <select style='width:120px' class='form-control select_column'
                                name='select_column[{{$k}}]'>
                            <option value=''>** Set Column for {{$column}}</option>
                            @foreach($data_import_column as $importColumn)
                                <option value='{{$importColumn}}'>{{$importColumn}}</option>
                            @endforeach
                        </select>
                    </td>
                @endforeach
            </tr>
            </tbody>
        </table>


    </div>

    @push('bottom')
        <script type="text/javascript">
            $(function () {
                var total_selected_column = 0;
                setInterval(function () {
                    total_selected_column = 0;
                    $('.select_column').each(function () {
                        var n = $(this).val();
                        if (n) total_selected_column = total_selected_column + 1;
                    })
                }, 200);
            })

            function check_selected_column() {
                var total_selected_column = 0;
                $('.select_column').each(function () {
                    var n = $(this).val();
                    if (n) total_selected_column = total_selected_column + 1;
                })
                if (total_selected_column == 0) {
                    swal("Oops...", "Please at least 1 column that should adjusted...", "error");
                    return false;
                }
                return true;
                
            }
        </script>
    @endpush

    <div class="box-footer">
        <div class='pull-right'>
            <a onclick="if(confirm('Are you sure want to leave ?')) location.href='{{ CRUDBooster::mainpath("import-data") }}'"
               href='javascript:;' class='btn btn-default'>Cancel</a>
            <input type='submit' class='btn btn-primary' name='submit'
                   onclick='return check_selected_column()' value='Import Data'/>
        </div>
    </div>
</form>
