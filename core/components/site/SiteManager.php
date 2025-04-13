<?php

namespace Simp\Core\components\site;

use Simp\Core\lib\installation\SystemDirectory;
use Simp\Core\lib\memory\cache\Caching;
use Symfony\Component\Yaml\Yaml;

class SiteManager extends SystemDirectory
{
    protected array $basic_settings = [];

    public function __construct()
    {
        parent::__construct();
        $site_file = $this->setting_dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'basic.site.setting.yml';
        if (file_exists($site_file)) {
            $this->basic_settings = Yaml::parseFile($site_file);
        }
    }

    public function get(string $key, $default = null)
    {

        $function = function(array $setting) use ($key, &$function) {
            foreach ($setting as $setting_key => $setting_value) {
                if ($setting_key === $key) {
                    return $setting_value;
                }
                elseif (is_array($setting_value)) {
                  return $function($setting_value);
                }
            }
        };

        foreach ($this->basic_settings as $setting) {
            if (isset($setting[$key])) {
                return $setting[$key];
            }
            elseif (is_array($setting)) {
               $f = $function($setting);
               if ($f) {
                   return $f;
               }
            }
        }
    }

    public static function factory(): static
    {
        return new static();
    }
}