<?php

namespace Simp\Core\modules\cron;


use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Simp\Core\lib\installation\SystemDirectory;
use Simp\Core\lib\memory\cache\Caching;
use Symfony\Component\Yaml\Yaml;

class Cron
{
    protected array $jobs = [];

    /**
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function __construct()
    {
        $default_crons = Caching::init()->get('default.admin.cron.jobs') ?? [];
        if (!empty($default_crons) && \file_exists($default_crons)) {
            $this->jobs = Yaml::parseFile($default_crons) ?? [];
        }

        $system = new SystemDirectory;
        $custom_crons = $system->setting_dir . \DIRECTORY_SEPARATOR . 'cron' . \DIRECTORY_SEPARATOR . 'custom_cron.yml';
        if (!\is_dir( $system->setting_dir . \DIRECTORY_SEPARATOR . 'cron' )) {
            @\mkdir( $system->setting_dir . \DIRECTORY_SEPARATOR . 'cron', 0777, true);
        }
        if (!\file_exists($custom_crons)) {
            @\touch($custom_crons);
        }
        $custom = Yaml::parseFile($custom_crons) ?? [];
        $this->jobs = [...$this->jobs, ...$custom];
    }

    public function getCrons(): array {
        return $this->jobs;
    }

    public function add(string $name, array $data) {
        $this->jobs[$name] = $data;
         $system = new SystemDirectory;
        $custom_crons = $system->setting_dir . \DIRECTORY_SEPARATOR . 'cron' . \DIRECTORY_SEPARATOR . 'custom_cron.yml';
        if (\file_exists($custom_crons)) {
            $d = Yaml::dump($this->jobs, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
            return \file_put_contents($custom_crons, $d);
        }
        return false;
    }

    public static function factory(): Cron {
        return new self();
    }
}
