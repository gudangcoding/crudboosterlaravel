@if(CRUDBooster::canRead() && $buttonDetail)
    <a class='btn btn-xs btn-primary btn-detail' title='{{cbTrans("action_detail_data")}}'
       href='{{CRUDBooster::mainpath("detail/".$row->$pk)."?return_url=".urlencode(Request::fullUrl())}}'>{{cbTrans("action_detail_data")}}</a>
@endif

@if(CRUDBooster::canUpdate() && $buttonEdit)
    <a class='btn btn-xs btn-success btn-edit' title='{{cbTrans("action_edit_data")}}'
       href='{{CRUDBooster::mainpath("edit/".$row->$pk)."?return_url=".urlencode(Request::fullUrl())."&parent_id=".g("parent_id")."&parent_field=".$parent_field }}'>{{cbTrans("action_edit_data")}}</a>
@endif

@if(CRUDBooster::canDelete() && $buttonDelete)
    <?php $url = CRUDBooster::mainpath("delete/".$row->$pk);?>
    <a class='btn btn-xs btn-warning btn-delete'
       title='{{cbTrans("action_delete_data")}}' href='javascript:;'
       onclick='{{CRUDBooster::deleteConfirm($url)}}'>{{cbTrans("action_delete_data")}}</a>
@endif