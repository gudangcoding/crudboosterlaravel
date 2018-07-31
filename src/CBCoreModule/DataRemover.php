<?php

namespace Crocodicstudio\Crudbooster\CBCoreModule;

use Illuminate\Support\Facades\Schema;
use Crocodicstudio\Crudbooster\Helpers\DbInspector;

class DataRemover
{
    private $ctrl;

    /**
     * DataRemover constructor.
     *
     * @param $ctrl
     */
    public function __construct($ctrl)
    {
        $this->ctrl =  $ctrl;
    }

    /**
     * @param $idsArray
     */
    private function deleteIds(array $idsArray)
    {
        $query = $this->ctrl->table()->whereIn(DbInspector::findPk($this->ctrl->table), $idsArray);
        if (Schema::hasColumn($this->ctrl->table, 'deleted_at')) {
            $query->update(['deleted_at' => YmdHis()]);
        } else {
            $query->delete();
        }
    }

    /**
     * @param $idsArray
     */
    public function doDeleteWithHook(array $idsArray)
    {
        $this->ctrl->hookBeforeDelete($idsArray);
        $this->deleteIds($idsArray);
        $this->ctrl->hookAfterDelete($idsArray);
        event('cb.dataDeleted', [$this->ctrl->table, $idsArray, YmdHis(), cbUser()]);
    }
}