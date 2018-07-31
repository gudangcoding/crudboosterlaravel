@extends('crudbooster::admin_template')

@section('content')


    @include("crudbooster::index._index.statistics" , ['index_statistic' => $index_statistic])


    @if(!empty($preIndexHtml))
        {!! $preIndexHtml !!}
    @endif


    @if(g('return_url'))
        <p>
            <a href='{{g("return_url")}}'>
                <i class='fa fa-chevron-circle-{{ cbTrans('left') }}'></i>
                &nbsp; {{ cbTrans('form_back_to_list',['module'=>urldecode(g('label'))])}}
            </a>
        </p>
    @endif


    @include("crudbooster::index._index.parent_table", ['parent_table' => $parent_table])


    <div class="box">
        <div class="box-header">
            @if($buttonBulkAction && ( ($buttonDelete && CRUDBooster::canDelete()) || $buttonSelected) )
                @include("crudbooster::index._index.header_btn")
            @endif
            <div class="box-tools pull-{{ cbTrans('right') }}"
                 style="position: relative;margin-top: -5px;margin-right: -10px">

                @if($buttonFilter)
                    <a style="margin-top:-23px" href="javascript:void(0)" id='btn_advanced_filter'
                       data-url-parameter='{{$build_query}}' title='{{ cbTrans('filter_dialog_title')}}'
                       class="btn btn-sm btn-default {{(Request::get('filter_column'))?'active':''}}">
                        <i class="fa fa-filter"></i> {{ cbTrans("button_filter")}}
                    </a>
                @endif

            @include("crudbooster::index._index.search", ['parameters' => request()->all()])

            @include("crudbooster::index._index.pagination_select", ['limit' => $limit ])

            </div>

            <br style="clear:both"/>

        </div>
        <div class="box-body table-responsive no-padding">
            @include("crudbooster::index._index.table")
        </div>
    </div>

    @if(!empty($postIndexHtml))
        {!! $postIndexHtml !!}
    @endif

@endsection
