<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JobLog extends Model
{
    protected $table = 'job_logs';
    protected $guarded = ['id'];
    protected $appends = ['logs'];

    public function getLogsAttribute(  )
    {
        $logs = @json_decode($this->payload);
        return $logs;
    }
}
