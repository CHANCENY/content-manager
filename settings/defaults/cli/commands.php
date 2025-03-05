<?php

$vendor = getcwd() . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (!file_exists($vendor)) {
    die('vendor directory not found. please run commands from root directory');
}

require_once $vendor;

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
use Symfony\Component\Yaml\Yaml;

global $system;
$system = new SystemDirectory();

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
 * @throws Exception
 */
function cache_clear(): void
{
    global $system;
    global $manifest_file;

    $manifest_file = $system->schema_dir . DIRECTORY_SEPARATOR . 'manifest.yml';
    $schema = file_exists($manifest_file) ? Yaml::parseFile($manifest_file, Yaml::PARSE_OBJECT_FOR_MAP) : [];

    /**
     * filesystem_installed
     * session_installed
     * caching_installed
     * database_installed
     * project_installed
     * theme_installed
     *
     * finished
     * project_cached
     * route_cached
     * service_cached
     * setting_cached
     * default_loaded
     * closed
     */

    if (!empty($schema->filesystem_installed)) {
        $refresh_schema = $system->schema_dir . DIRECTORY_SEPARATOR . 'refresh.yml';
        if (file_exists($refresh_schema)) {
            $yml = Yaml::parseFile($refresh_schema);
            $file_manifest = $system->schema_dir . DIRECTORY_SEPARATOR . 'manifest.yml';
            if (file_put_contents($file_manifest, Yaml::dump($yml))) {
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
                echo "cache cleared\n";
            }
        }
    }
}

return [
    'cache:clear' => "cache_clear"
];