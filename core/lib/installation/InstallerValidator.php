<?php

namespace Simp\Core\lib\installation;

use Exception;
use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheDriverNotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheExtensionNotInstalledException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidTypeException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Simp\Core\lib\file\file_system\stream_wrapper\SettingStreamWrapper;
use Simp\Core\lib\memory\cache\Caching;
use Simp\Core\lib\routes\Route;
use Simp\Core\lib\themes\TwigResolver;
use Simp\Core\modules\database\Database;
use Simp\StreamWrapper\WrapperRegister\WrapperRegister;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Yaml\Yaml;
use Throwable;

/**
 * @class This class is for managing site set up checking requirements.
 */

class InstallerValidator extends SystemDirectory {

    protected object $installer_schema;

    public function __construct() {

        parent::__construct();
        $schema_file = $this->schema_dir."/manifest.yml";
        if (!file_exists($schema_file)) {
            die("Step up manifest file does not exist");
        }
        $this->installer_schema = Yaml::parseFile($schema_file, Yaml::PARSE_OBJECT_FOR_MAP);

        // Run
        $globals = $this->installer_schema->run->globals;
        foreach ($globals as $key => $value) {
            $GLOBALS[$value] = null;
        }
        $GLOBALS['system_store'] = $this;
        $GLOBALS['request_start_time'] = microtime(true);
    }

    /**
     * Check if all file system requirements are met.
     * @return bool
     */
    public function validateFileSystem(): bool {
        return $this->installer_schema->filesystem_installed !== "pending";
    }

    /**
     * Check if Session are set correctly.
     * @return bool
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheDriverNotFoundException
     * @throws PhpfastcacheExtensionNotInstalledException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheInvalidConfigurationException
     * @throws PhpfastcacheInvalidTypeException
     * @throws PhpfastcacheLogicException
     */
    public function validateSession(): bool {
        if( $this->installer_schema->session_installed === "pending") {
            return false;
        }

        $session_store = null;
        if ( $this->installer_schema->session_installed === 'file_based') {
            CacheManager::setDefaultConfig(new ConfigurationOption([
                'path' => $this->var_dir . '/sessions',
            ]));
            $session_store = CacheManager::getInstance('files');
        }
        else {
            @session_start();
        }
        $GLOBALS["session_store"] = $session_store;
        return true;
    }

    /**
     * Check if database is set correctly.
     * @return bool
     */
    public function validateDatabase(): bool {
        return $this->installer_schema->database_installed !== "pending";
    }

    /**
     * Check if project is set to handle request.
     * @return bool
     */
    public function validateProject(): bool {
        if ($this->installer_schema->project_installed === "pending") {
            return false;
        }
        $this->setUpProject();
        return true;
    }

    /**
     * @throws PhpfastcacheDriverNotFoundException
     * @throws PhpfastcacheInvalidConfigurationException
     * @throws PhpfastcacheExtensionNotInstalledException
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheInvalidTypeException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function validateCaching(): bool
    {
        if($this->installer_schema->caching_installed === "pending") {
            return false;
        }

        $cache_store = null;
        if ($this->installer_schema->caching_installed_steps->fast_cache) {

            CacheManager::setDefaultConfig(new ConfigurationOption([
                'path' => $this->var_dir . '/cache',
            ]));
            $cache_store = CacheManager::getInstance('files');
        }
        $GLOBALS["caching"] = $cache_store;
        return true;
    }

    /**
     * @throws Exception
     */
    public function setUpFileSystem(): void {

        $streams = $this->installer_schema->filesystem_installed_steps->streams;

        // Register StreamWrapper
        foreach ($streams as $wrapper) {
            $wrapper = (array) $wrapper;
            $wrapper_name = key($wrapper);
            $wrapper_handler = $wrapper[$wrapper_name];
            WrapperRegister::register($wrapper_name, $wrapper_handler);
        }

        // Create all need directories.
        $directories = $this->installer_schema->filesystem_installed_steps->directories;
        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                if (!mkdir($directory,recursive: true) ) {
                    throw new Exception("Directory $directory failed to create");
                }
            }
        }

        // Move the default_files
        $default = __DIR__ . '/default_installation_configs';
        $directory_list = array_diff(scandir($default), ['..', '.']);

        foreach ($directory_list as $directory) {
            $full_path = $default . DIRECTORY_SEPARATOR . $directory;
            $this->recursive_mover($full_path, $directory);
        }

        $this->installer_schema->filesystem_installed = "installed";
        $this->installer_schema->finished->default_loaded = true;
    }

    /**
     * @throws PhpfastcacheDriverNotFoundException
     * @throws PhpfastcacheInvalidConfigurationException
     * @throws PhpfastcacheExtensionNotInstalledException
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheInvalidTypeException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function setUpSession(): void
    {
        $session_store = null;
        $installed = null;
        if ($this->installer_schema->session_installed_steps->file_based) {
            CacheManager::setDefaultConfig(new ConfigurationOption([
                'path' => $this->var_dir . '/sessions',
            ]));
            $session_store = CacheManager::getInstance('files');
            $installed = 'file_based';
        }
        elseif ($this->installer_schema->session_installed_steps->default) {
            $installed = 'default';
            @session_start();
        }
        $GLOBALS["session_store"] = $session_store;
        $this->installer_schema->session_installed = $installed;
    }

    /**
     * @throws PhpfastcacheDriverNotFoundException
     * @throws PhpfastcacheInvalidConfigurationException
     * @throws PhpfastcacheExtensionNotInstalledException
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheInvalidTypeException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function setUpCaching(): void
    {
        $cache_store = null;
        $installed = null;
        if ($this->installer_schema->caching_installed_steps->fast_cache) {

            CacheManager::setDefaultConfig(new ConfigurationOption([
                'path' => $this->var_dir . '/cache',
            ]));
            $cache_store = CacheManager::getInstance('files');
            $installed = 'fast_cache';
        }
        $GLOBALS["caching"] = $cache_store;
        $this->installer_schema->finished->setting_cached = true;
        $this->installer_schema->caching_installed = $installed;
        $this->installer_schema->finished->route_cached = true;
    }

    public function setUpDatabase(): void
    {
        if ($this->installer_schema->database_installed_steps->check_configuration) {

            if(!empty($GLOBALS['stream_wrapper']['setting']) && $GLOBALS['stream_wrapper']['setting'] instanceof SettingStreamWrapper) {

                $database_settings = $this->setting_dir ."/database/database.yml";
                if (!file_exists($database_settings)) {
                    $redirect = new RedirectResponse('/admin/configure/database');
                    $redirect->send();
                }
                $database_settings = Yaml::parseFile($database_settings);
                $database = new Database(...$database_settings);
                if ($database->con()->getAttribute(\PDO::ATTR_DRIVER_NAME) !== 'mysql') {
                    die("Database connection failed");
                }
                $default_table_file = $this->setting_dir . DIRECTORY_SEPARATOR . 'defaults'. DIRECTORY_SEPARATOR .'database' . DIRECTORY_SEPARATOR . 'default.yml';
                if (file_exists($default_table_file)) {
                    $tables = Yaml::parseFile($default_table_file);
                    if (is_array($tables)) {

                        try{
                            foreach ($tables['table'] as $table) {
                                $statement = $database->con()->prepare($table);
                                $statement->execute();
                            }
                        }catch (Throwable $exception){}
                    }
                    $this->installer_schema->database_installed = 'installed';
                }
            }
        }
    }

    /**
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function setUpProject(): void
    {
        $this->cacheDefaults();
    }

    public function finishInstall(): void
    {
        if ($this->installer_schema->closed === false && $this->installer_schema->twig_setting->debug === false) {
            $schema_path = $this->schema_dir . DIRECTORY_SEPARATOR . 'manifest.yml';
            if (file_exists($schema_path)) {
                $this->installer_schema->closed = true;
                $array = json_decode(json_encode($this->installer_schema), true);
                file_put_contents($schema_path, Yaml::dump($array,Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
            }
        }
    }

    protected function developerCustomRoutes(): array
    {
        $developer_routes_file = $this->setting_dir . DIRECTORY_SEPARATOR . '/custom/routes.yml';
        if (file_exists($developer_routes_file)) {
            return Yaml::parseFile($developer_routes_file);
        }
        return [];
    }

    /**
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function cacheDefaults(): void
    {
        if ($this->installer_schema->twig_setting->debug) {
            $setting_root = $this->setting_dir . DIRECTORY_SEPARATOR . 'defaults';
            $files = array_diff(scandir($setting_root) ?? [], ['..', '.']);
            $default_keys = [];
            foreach ($files as $file) {
                $full_path = $setting_root . DIRECTORY_SEPARATOR . $file;
                if (is_dir($full_path)) {
                    $this->recursive_caching_defaults($full_path, $default_keys);
                }
            }
            $theme_list = array_diff(scandir($this->theme_dir) ?? [], ['..', '.']);
            foreach ($theme_list as $theme) {
                $theme_path = $this->theme_dir . DIRECTORY_SEPARATOR . $theme;
                if (is_dir($theme_path)) {
                    $this->recursive_theme_caching($theme_path, $theme, $default_keys);
                }
            }
            Caching::init()->set('system.theme.keys', $default_keys);

            $default_route = Caching::init()->get('default.admin.routes');
            $routes = [];
            if (!empty($default_route) && file_exists($default_route)) {
                $routes = Yaml::parseFile($default_route);
            }

            if ($routes_custom = $this->developerCustomRoutes()) {
                $routes = array_merge($routes, $routes_custom);
            }

            foreach ($routes as $key=>$route) {
                $route = new Route($key, $route);
                Caching::init()->set($key, $route);
            }
            Caching::init()->set('system.routes.keys', array_keys($routes));
        }

    }

    protected function recursive_caching_defaults(string $directory, &$keys): void
    {
        $list = array_diff(scandir($directory) ?? [], ['..', '.']);
        foreach ($list as $file) {
            $file_path = $directory . DIRECTORY_SEPARATOR . $file;
            $list_name = explode('.', $file);
            $type = end($list_name) === 'twig' ? 'view' : 'admin';
            $list_n = array_slice($list_name, 0, -1);
            $key_name = "default.".$type.".". implode('.', $list_n);
            if (is_file($file_path)) {
                if ($type === 'view') {
                    $keys[] = $key_name;
                    $file_path = new TwigResolver($file_path);
                }
                Caching::init()->set($key_name, $file_path);
            }
        }
    }

    protected function recursive_theme_caching(string $directory, string $theme ,&$keys): void
    {
        $list = array_diff(scandir($directory) ?? [], ['..', '.']);
        foreach ($list as $file) {
            $file_path = $directory . DIRECTORY_SEPARATOR . $file;
            $list_name = explode('.', $file);
            $type = end($list_name) === 'twig' ? 'view' : 'file';
            $list_n = array_slice($list_name, 0, -1);
            $key_name = "$theme.".$type.".". implode('.', $list_n);
            if (is_file($file_path)) {
                if ($type === 'view') {
                    $keys[] = $key_name;
                    $file_path = new TwigResolver($file_path);
                }
                Caching::init()->set($key_name, $file_path);
            }
        }
    }

    protected function recursive_mover(string $directory, string $directory_name): void
    {
        $settings = $this->setting_dir . DIRECTORY_SEPARATOR . 'defaults';
        if (!is_dir($settings)) {
            mkdir($settings);
        }
        $settings .= DIRECTORY_SEPARATOR . $directory_name;
        if (!is_dir($settings)) {
            mkdir($settings);
        }
        $list = array_diff(scandir($directory), ['..', '.']);
        foreach ($list as $file) {
            $file_full = $directory . DIRECTORY_SEPARATOR . $file;
            $new_file = $settings . DIRECTORY_SEPARATOR . $file;
            if (is_dir($file_full)) {
                $this->recursive_mover($file_full, $file);
            }
            else {
                @copy($file_full,$new_file);
            }
        }
    }
}