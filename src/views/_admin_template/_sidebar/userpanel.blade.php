<div class="user-panel">
    <div class="pull-{{ cbTrans('left') }} image">
        <img src="{{ cbUser()->myPhoto() }}" style="width:45px;height:45px;" class="img-circle" alt="{{ cbTrans('user_image') }}"/>
    </div>
    <div class="pull-{{ cbTrans('left') }} info">
        <p>{{ cbUser()->name }}</p>
        <!-- Status -->
        <a href="#">{!! cbIcon('circle text-success') !!} {{ cbTrans('online') }}</a>
    </div>
</div>