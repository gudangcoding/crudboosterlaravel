<ul class="nav nav-tabs">
    <li>
        <a href="{{ CRUDBooster::mainpath() }}">{!! cbIcon('file') !!}</i> List API</a>
    </li>

    <li class="active">
        <a href="{{ CRUDBooster::mainpath('generator') }}">{!! cbIcon('cog') !!}</i> API Generator</a>
    </li>

    <li>
        <a href="{{ CRUDBooster::mainpath('secret-key') }}">{!! cbIcon('key') !!}</i> API Secret Key</a>
    </li>

    <li>
        <a href="{{ url('api/doc')}}" target="_blank">{!! cbIcon('book') !!}</i> API Documentation</a>
    </li>
</ul>