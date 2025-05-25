<?php

namespace Simp\Core\modules\cron\event;

class CronSetting
{
    /**
     * Defines the frequency setting for a specific operation or task.
     *
     * every|minute
     * every|hour
     * every|day
     * every|week
     * every|month
     * every|year
     * ontime|06:00:00
     * once|2021-01-01
     *
     * Define the frequency setting for a specific operation or task.
     */
    public string $frequency = 'every:hour';

    /**
     * The default timezone set for the application.
     */
    public string $timezone = 'UTC';

    /**
     * The name assigned to the default cron job.
     */
    public string $cron_name = 'default';
}
