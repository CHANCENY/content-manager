<?php

namespace Simp\Core\lib\app;

use Phpfastcache\Drivers\Files\Driver;
use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheDriverNotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheExtensionNotInstalledException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidTypeException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Simp\Core\lib\installation\InstallerValidator;
use Simp\Core\lib\installation\SystemDirectory;
use Simp\Core\lib\memory\cache\Caching;
use Simp\Core\lib\routes\Route;
use Simp\Core\modules\config\ConfigManager;
use Simp\Core\modules\logger\ErrorLogger;
use Symfony\Component\HttpFoundation\Request;
use Simp\Router\Route as Router;
use Symfony\Component\Yaml\Yaml;
use Throwable;

class App
{
    /**
     * @throws PhpfastcacheDriverNotFoundException
     * @throws PhpfastcacheInvalidConfigurationException
     * @throws PhpfastcacheExtensionNotInstalledException
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheInvalidTypeException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws \Exception
     */
    public function __construct()
    {
        // Prepare default timezone
        $set_up_wizard = new InstallerValidator();

        /**
         * Check for filesystem and set up required areas.
         */
        if (!$set_up_wizard->validateFileSystem()) {
            $set_up_wizard->setUpFileSystem();
        }

       // check for session setting and start sessions.
        if (!$set_up_wizard->validateSession()) {
            $set_up_wizard->setUpSession();
        }

        if (!$set_up_wizard->validateCaching()) {
            $set_up_wizard->setUpCaching();
        }

        if(!$set_up_wizard->validateProject()) {
            $set_up_wizard->setUpProject();
        }

        // Check for database only if we are not on /admin/configure/database
        $request = Request::createFromGlobals();
        $current_uri = $request->getRequestUri();
        $database_form_route = $GLOBALS['caching']->getItem('route.database.form.route');
        $database_form_route = $database_form_route->isHit() ? $database_form_route->get() : null;


        /**@var Route $database_form_route **/
        if ((!empty($database_form_route) && $database_form_route?->route_path != $current_uri) || $current_uri !== '/admin/configure/database') {
            if (!$set_up_wizard->validateDatabase()) {
                $set_up_wizard->setUpDatabase();
            }
        }

        $set_up_wizard->finishInstall();

        $config = ConfigManager::config()->getConfigFile("development.setting");
        if ($config?->get('enabled') === 'yes') {
            try{
                $this->mapRouteListeners();
            }catch (Throwable $exception){
                ErrorLogger::logger()->logError($exception->__toString());
                echo "Unexpected error encountered";
            }
        }
        else {
            $this->mapRouteListeners();
        }


        // Log some executions
        $start_time = $GLOBALS['request_start_time'];
        $end_time = microtime(true);
        $time_elapsed = $end_time - $start_time;
        $memory_elapsed = memory_get_usage();
        $cpu_usage = getrusage();
        $user_cpu = $cpu_usage["ru_utime.tv_sec"] + $cpu_usage["ru_utime.tv_usec"] / 1e6;
        $system_cpu = $cpu_usage["ru_stime.tv_sec"] + $cpu_usage["ru_stime.tv_usec"] / 1e6;

        $log_file = $GLOBALS['system_store']->setting_dir . '/logs/app.log';
        $log_content = "start:{$start_time} elapsed:{$time_elapsed} end:{$end_time} memory:{$memory_elapsed} system_usage:{$system_cpu} user_usage:{$user_cpu}\n";
        file_put_contents($log_file, $log_content, FILE_APPEND);
        if (isset($GLOBALS['temp_error_log'])) {
            unset($GLOBALS['temp_error_log']);
        }
    }

    public static function runApp(): App
    {
        return new App();
    }

    /**
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function mapRouteListeners(): void
    {
        /**@var Driver $cache **/
        $cache = $GLOBALS['caching'];
        $route_keys = $cache->getItem('system.routes.keys');

        $system = new SystemDirectory;
      
        $middleware_file = $system->setting_dir . DIRECTORY_SEPARATOR . 'middleware' . DIRECTORY_SEPARATOR
        . 'middleware.yml';
        if (!file_exists($middleware_file)) {
            $file = Caching::init()->get('default.admin.middleware');
            if (!empty($file) && file_exists($file)) {
                @mkdir($system->setting_dir . DIRECTORY_SEPARATOR . 'middleware');
                @copy($file,$middleware_file);
            }
        }

        $router = new Router($middleware_file);

        if ($route_keys->isHit()) {
            $route_keys = $route_keys->get();

            foreach ($route_keys as $route_key) {
                $route = $cache->getItem($route_key);
                if ($route->isHit()) {
                    $route = $route->get();

                    /**@var Route $route**/
                    // check methods
                    $methods = $route->method;
                    $path = $route->route_path;
                    $name = $route->controller_method;
                    $controller = $route->controller. "@" . $name;

                    $options = [
                        'access' => $route->access,
                        'route' => $route,
                        'key' => $route_key,
                    ];

                    if (count($methods) > 0) {
                        foreach ($methods as $method) {
                            $method_single = strtolower($method);
                            $router->$method_single($path, $name,$controller, $options);
                        }
                    }
                }
            }
        }
        else {
            if (!empty($GLOBALS["routes"])) {
                foreach ($GLOBALS["routes"] as $k=>$route) {

                    $route['route_id'] = $k;
                    $methods = $route['method'];
                    $path = $route['path'];
                    $name = $route['controller']['method'];
                    $controller = $route['controller']['class']. "@" . $name;
                    
                    // TODO: add more options values.
                    $options = [
                       'access'=> $route->access,
                       'route' => $route,
                        'key' => $k,
                    ];

                    if (count($methods) > 0) {
                        foreach ($methods as $method) {
                            $method_single = strtolower($method);
                            $router->$method_single($path, $name,$controller, $options);
                        }
                    }

                }
            }
        }
    }

    /**
     * @throws PhpfastcacheExtensionNotInstalledException
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheInvalidTypeException
     * @throws PhpfastcacheDriverNotFoundException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheInvalidConfigurationException
     * @throws PhpfastcacheLogicException
     */
    public static function consoleApp(): void
    {
        $set_up_wizard = new InstallerValidator();

        /**
         * Check for filesystem and set up required areas.
         */
        if (!$set_up_wizard->validateFileSystem()) {
            $set_up_wizard->setUpFileSystem();
        }

        // check for session setting and start sessions.
        if (!$set_up_wizard->validateSession()) {
            $set_up_wizard->setUpSession();
        }

        if (!$set_up_wizard->validateCaching()) {
            $set_up_wizard->setUpCaching();
        }

        if(!$set_up_wizard->validateProject()) {
            $set_up_wizard->setUpProject();
        }
    }

}