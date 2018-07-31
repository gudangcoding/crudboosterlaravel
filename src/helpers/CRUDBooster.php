<?php

namespace Crocodicstudio\Crudbooster\Helpers;

error_reporting(E_ALL ^ E_NOTICE);
use Crocodicstudio\Crudbooster\CBCoreModule\Facades\CbRouter;
use Crocodicstudio\Crudbooster\CBCoreModule\ViewHelpers;
use Crocodicstudio\Crudbooster\Modules\PrivilegeModule\GetCurrentX;
use Crocodicstudio\Crudbooster\Modules\PrivilegeModule\PrivilegeHelpers;
use Session;
use Request;
use Schema;
use Cache;
use DB;
use Route;
use Config;
use Validator;

class CRUDBooster
{
    use PrivilegeHelpers, GetCurrentX;

    public static function parseSqlTable($table)
    {
        $f = explode('.', $table);

        if (count($f) == 1) {
            return ["table" => $f[0], "database" => cbConfig('MAIN_DB_DATABASE')];
        }
        if (count($f) == 2) {
            return ["database" => $f[0], "table" => $f[1]];
        }

        if (count($f) == 3) {
            return ["table" => $f[0], "schema" => $f[1], "table" => $f[2]];
        }

        return false;
    }

    public static function adminPath($path = null)
    {
        return url(cbAdminPath().'/'.$path);
    }

    public static function deleteConfirm($redirectTo)
    {
        return view('crudbooster::_admin_template.deleteConfirmModal', ['redirectTo'=> $redirectTo])->render();
    }

    public static function getValueFilter($field)
    {
        return self::getFilter($field, 'value');
    }

    private static function getFilter($field, $index)
    {
        $filter = request('filter_column');
        if ($filter[$field]) {
            return $filter[$field][$index];
        }
    }

    public static function getSortingFilter($field)
    {
        return self::getFilter($field, 'sorting');
    }

    public static function getTypeFilter($field)
    {
        return self::getFilter($field, 'type');
    }

    public static function first($table, $id)
    {
        $table = self::parseSqlTable($table)['table'];

        if (! is_array($id)) {
            $id = [DbInspector::findPK($table) => $id];
        }

        return DB::table($table)->where($id)->first();
    }

    public static function urlFilterColumn($key, $type, $value = '', $singleSorting = true)
    {
        return \Crocodicstudio\Crudbooster\CBCoreModule\Index\ViewHelpers::urlFilterColumn($key, $type, $value, $singleSorting);
    }

    public static function mainpath($path = null)
    {
        $controllerName = strtok(Route::currentRouteAction(), '@');
        // $controllerName = array_pop(explode('\\', $controllerName));

        $controllerName = basename($controllerName);
        $routeUrl = route($controllerName.'GetIndex');

        if (! $path) {
            return trim($routeUrl, '/');
        }

        if (substr($path, 0, 1) == '?') {
            return trim($routeUrl, '/').$path;
        }

        return $routeUrl.'/'.$path;
    }

    public static function listCbTables()
    {
        $tables = DbInspector::listTables();

        $filter = function ($tableName) {

            if ($tableName == config('database.migrations')) {
                return false;
            }

            if ($tableName == 'cms_users') {
                return true;
            }

            if (starts_with($tableName, 'cms_')) {
                return false;
            }

            return true;
        };

        return array_filter($tables, $filter);
    }

    public static function getUrlParameters($exception = null)
    {
        return ViewHelpers::getUrlParameters($exception);
    }

    /*    public static function isExistsController($table)
        {
            $ctrlName = ucwords(str_replace('_', ' ', $table));
            $ctrlName = str_replace(' ', '', $ctrlName).'Controller.php';
            $path = base_path(controllers_dir());
            $path2 = base_path(controllers_dir()."ControllerMaster/");

            if (file_exists($path.'Admin'.$ctrlName) || file_exists($path2.'Admin'.$ctrlName) || file_exists($path2.$ctrlName)) {
                return true;
            }

            return false;
        }*/

    public static function routeController(string $prefix, string $controller, $namespace = '')
    {
        CbRouter::routeController($prefix, $controller, $namespace);
    }

    /*
    | -------------------------------------------------------------
    | Alternate route for Laravel Route::controller
    | -------------------------------------------------------------
    | $prefix       = path of route
    | $controller   = controller name
    | $namespace    = namespace of controller (optional)
    |
    */

    public static function redirect($to, $message, $type = 'warning')
    {
        if (Request::ajax()) {
            respondWith()->json(['message' => $message, 'message_type' => $type, 'redirect_url' => $to]);
        }

        respondWith(redirect($to)->with(['message' => $message, 'message_type' => $type]));
    }

    public static function allowOnlySuperAdmin()
    {
        event('cb.unauthorizedTryToSuperAdminArea', [cbUser(), request()->fullUrl()]);
        if (self::isSuperadmin()) {
            return true;
        }

        self::denyAccess();
    }
}
