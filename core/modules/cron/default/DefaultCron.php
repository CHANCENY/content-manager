<?php

namespace Simp\Core\modules\cron\default;

use Simp\Core\modules\cron\event\CronExecutionResponse;
use Simp\Core\modules\cron\event\CronSubscriber;

class DefaultCron implements CronSubscriber
{

    public function run(string $name): CronExecutionResponse
    {
        $start = time(); // seconds only
        sleep(50);
        $end = time(); // seconds only
        $execution_time = $end - $start;

        $response = new CronExecutionResponse();
        $response->message = 'Default cron executed successfully.';
        $response->status = 200;
        $response->execution_time = $execution_time;      // int
        $response->start_timestamp = $start;              // int
        $response->end_timestamp = $end;                  // int
        $response->name = $name;
        return $response;
    }

}