<?php

namespace Simp\Core\modules\cron\default;

use Simp\Core\modules\cron\event\CronExecutionResponse;
use Simp\Core\modules\cron\event\CronSubscriber;

class DefaultCron implements CronSubscriber
{

    public function run(string $name): CronExecutionResponse
    {
        $response = new CronExecutionResponse();
        $response->message = 'Default cron executed successfully.';
        $response->status = 200;
        $response->execution_time = 1;
        $response->start_timestamp = time();
        $response->end_timestamp = time();
        $response->name = $name;
        return $response;
    }

}