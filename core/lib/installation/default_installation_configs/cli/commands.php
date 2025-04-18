<?php

$vendor = getcwd() . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (!file_exists($vendor)) {
    die('vendor directory not found. please run commands from root directory');
}

require_once $vendor;

use Simp\Core\lib\memory\cache\Caching;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Yaml\Yaml;
use Simp\Core\modules\user\entity\User;
use Simp\Core\lib\installation\SystemDirectory;
use Simp\Core\lib\installation\InstallerValidator;
use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidTypeException;
use Phpfastcache\Exceptions\PhpfastcacheDriverNotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException;
use Phpfastcache\Exceptions\PhpfastcacheExtensionNotInstalledException;

global $system;
global $terminal_colors;
$terminal_colors = [
    'black' => '0;30',
    'red' => '0;31',
    'green' => '0;32',
    'yellow' => '0;33',
    'blue' => '0;34',
    'magenta' => '0;35',
    'cyan' => '0;36',
    'white' => '0;37',
    'bold_black' => '1;30',
    'bold_red' => '1;31',
    'bold_green' => '1;32',
    'bold_yellow' => '1;33',
    'bold_blue' => '1;34',
    'bold_magenta' => '1;35',
    'bold_cyan' => '1;36',
    'bold_white' => '1;37',
];

$system = new SystemDirectory();

function cache_clear(array $options): void
{
    global $system;
    global $terminal_colors;

    if (count($options) !== 1) {

        echo "\033[" . $terminal_colors['red'] . "m exact one option is required" . PHP_EOL;
        echo PHP_EOL;
        echo "--option first expected values eg 
        --clear,
        --cache,
        --session,
        --twig
        --services
        --routing
        --forms
        --logs
        --db-logs
        " .PHP_EOL.PHP_EOL;
        echo "\033[0m";
        return;
    }

    $option = $options[0];
    $cleanable_paths = [
        '--cache' => $system->var_dir . DIRECTORY_SEPARATOR . 'cache',
        '--session' => $system->var_dir . DIRECTORY_SEPARATOR . 'sessions',
        '--twig' => $system->var_dir . DIRECTORY_SEPARATOR . 'twig',
        '--services' => $system->var_dir . DIRECTORY_SEPARATOR . 'services',
        '--routing' => $system->var_dir . DIRECTORY_SEPARATOR . 'routing',
        '--forms' => $system->var_dir . DIRECTORY_SEPARATOR . 'form-build',
        '--logs' => $system->setting_dir . DIRECTORY_SEPARATOR . 'logs',
        '--db-logs' => $system->setting_dir . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'database.log',
    ];

    $location = $cleanable_paths[$option] ?? null;

    if (!empty($location)) {

        if (is_file($location) && file_exists($location)) {
            unlink($location);
            echo "\033[" . $terminal_colors['green'] . "m clearing done successfully\033[0m\n";
            return;
        }

        $list = array_diff(scandir($location)?? [], ['.', '..']);

        $recursive = function($dir) use (&$recursive): void {
            $list_inner = array_diff(scandir($dir) ?? [], ['.', '..']);
            foreach ($list_inner as $item) {
                $full_inner_path = $dir . DIRECTORY_SEPARATOR . $item;
                if (is_file($full_inner_path) && file_exists($full_inner_path)) {
                    unlink($full_inner_path);
                }
                elseif (is_dir($full_inner_path)) {
                    $recursive($full_inner_path);
                    @rmdir($full_inner_path);
                }
            }
        };

        foreach ($list as $file) {
            $full_path = $location  . DIRECTORY_SEPARATOR . $file;
            if (is_file($full_path) && file_exists($full_path)) {
                unlink($full_path);
            }
            elseif (is_dir($full_path)) {
                $recursive($full_path);
                @rmdir($full_path);
            }
        }

        echo "\033[" . $terminal_colors['green'] . "m clearing done successfully\033[0m\n";
        return;
    }

    echo "\033[" . $terminal_colors['yellow'] . "m option not supported.\033[0m\n";
}

function user(array $options): void
{
    global $system;
    global $terminal_colors;
    $option = $options[0] ?? null;

    $supported = [
        '--find' => 'search_user',
        '--create' => 'create_user',
        '--update' => 'update_user',
        '--delete' => 'delete_user',
        '--block' => 'block_user',
    ];

    $found = $supported[$option] ?? null;
    if (empty($found)) {

        echo "\033[0m";
        echo "\033[" . $terminal_colors['red'] . "m exact one option is required" . PHP_EOL;
        echo PHP_EOL;
        echo "--option first expected values eg
        --find,
        --create,
        --update,
        --delete,
        --block,
        " .PHP_EOL.PHP_EOL;
        echo "\033[0m";
        return;
    }

    function search_user (): void {
        global $system;
        global $terminal_colors;

        while (true) {
            echo "You can search using email, uid, name".PHP_EOL. PHP_EOL;
            $input = trim(readline("Search User: "));

            if (is_numeric($input)) {
                $user = User::load($input);
                echo PHP_EOL;
                echo $user;
                echo PHP_EOL . PHP_EOL;
            }
            else {
                $users = User::filter($input);
                echo PHP_EOL;
                foreach($users as $user) {
                    echo PHP_EOL;
                    echo User::load($user['uid']);
                }
                echo PHP_EOL . PHP_EOL;
            }
            
        }

        return;
    };

    function create_user (): void {
        global $system;
        global $terminal_colors;

        echo PHP_EOL . PHP_EOL;
        $username = trim(readline("Enter username: "));
        $email = trim(readline("Enter email: "));
        $status = trim(readline("Enter status (1 or 0): "));
        $password = trim(readline("Enter password: "));
        $timezone = trim(readline("Enter timezone: "));
        $roles = trim(readline("Enter roles (commas separated): "));

        if (!empty($username) && !empty($email) && !empty($password)) {

            $result = User::create([
                'mail' => $email,
                'name' => $username,
                'status' => $status,
                'password' => $password,
                'time_zone' => $timezone,
                'roles' => strpos($roles, ',') ? explode(',', $roles) : [$roles]
            ]);

            if ($result) {
                echo PHP_EOL;
                echo "\033[" . $terminal_colors['green'] . "m created user successfully!\033[0m\n";
                return;
            }
            echo PHP_EOL;
            echo "\033[" . $terminal_colors['red'] . "m failed to create user!\033[0m\n";
            return;    
        }
        
        echo PHP_EOL;
        echo "\033[" . $terminal_colors['red'] . "m username, password, email are required!\033[0m\n";
        return;
    };

    function update_user (): void {
        global $system;
        global $terminal_colors;

        echo PHP_EOL . PHP_EOL;
        $uid = trim(readline("Enter user id: "));
        $username = trim(readline("Enter username: "));
        $email = trim(readline("Enter email: "));
        $password = trim(readline("Enter password: "));
        
        if (!empty($uid) && is_numeric($uid)) {

            $user = User::load($uid);
            if(!empty($username)) {
                $user->setName($username);
            }
            if(!empty($email)) {
                $user->setMail($email);
            }
            if(!empty($password)) {
                $user->setPassword(password_hash($password, PASSWORD_BCRYPT));
            }
            
            if ($user->update()) {
                echo PHP_EOL;
                echo "\033[" . $terminal_colors['green'] . "m update user successfully!\033[0m\n";
                return;
            }
            echo PHP_EOL;
            echo "\033[" . $terminal_colors['red'] . "m failed to update user!\033[0m\n";
            return;    
        }
    };

    function block_user (): void {
        global $system;
        global $terminal_colors;

        if (!empty($uid) && is_numeric($uid)) {

            $user = User::load($uid);
            $user->setStatus(true);

            if ($user->update()) {
                echo PHP_EOL;
                echo "\033[" . $terminal_colors['green'] . "m user blocked successfully!\033[0m\n";
                return;
            }
            echo PHP_EOL;
            echo "\033[" . $terminal_colors['red'] . "m failed to block user!\033[0m\n";
            return;    
        }
    };

    function delete_user (): void {
        global $system;
        global $terminal_colors;

        $uid = trim(readline('Enter user id:'));

        if (!empty($uid) && is_numeric($uid)) {

            $user = User::load($uid);
            if ($user->delete()) {
                echo PHP_EOL;
                echo "\033[" . $terminal_colors['green'] . "m user deleted successfully!\033[0m\n";
                return;
            }
            echo PHP_EOL;
            echo "\033[" . $terminal_colors['red'] . "m failed to delete user!\033[0m\n";
            return;    
        }
    };

    $found();

}

function faker(array $options): void
{
    global $system;
    global $terminal_colors;
    $option = $options[0] ?? null;

    $supported = [
        '--node' => 'node_faker_create',
        '--user' => 'faker_user_create',
    ];

    $found = $supported[$option] ?? null;
    if (empty($found)) {
        echo "\033[0m";
        echo "\033[" . $terminal_colors['red'] . "m exact one option is required" . PHP_EOL;
        echo PHP_EOL;
        echo "--option first expected values eg
        --node,
        --user,
        " .PHP_EOL.PHP_EOL;
        echo "\033[0m";
        return;
    }

    function node_faker_create()
    {
        global $terminal_colors;
        echo PHP_EOL."NODE FAKER CREATION STARTED".PHP_EOL.PHP_EOL;
        $content_type = readline("Content Type: ");
        if (empty($content_type)) {
            echo "\033[" . $terminal_colors['yellow'] . "m sorry content type machine_name is needed.\033[0m\n";
            return;
        }

        $faker = new \Simp\Core\modules\structures\content_types\faker_manager\FakerManager($content_type);
        $fields = $faker->getFillableFields();
        if (empty($fields)) {
            echo "\033[" . $terminal_colors['yellow'] . "m sorry content type has not fields.\033[0m\n";
            return;
        }

        $populate_mapper = [];

        $fakerMethods = [
            // 🧑 Personal
            'name' => 'Full name (e.g. John Doe)',
            'firstName' => 'First name (e.g. John)',
            'lastName' => 'Last name (e.g. Doe)',
            'title' => 'Title (e.g. Mr., Dr.)',
            'gender' => 'Gender (e.g. Male or Female)',
            'dateOfBirth' => 'Date of Birth',

            // 📍 Address
            'address' => 'Full address',
            'streetAddress' => 'Street address',
            'streetName' => 'Street name',
            'city' => 'City name',
            'postcode' => 'Postal code / ZIP',
            'state' => 'State name',
            'stateAbbr' => 'State abbreviation',
            'country' => 'Country name',
            'countryCode' => 'ISO country code',
            'latitude' => 'Latitude coordinate',
            'longitude' => 'Longitude coordinate',
            'buildingNumber' => 'Building number',
            'secondaryAddress' => 'Secondary address line (e.g. Apt. 2B)',
            'citySuffix' => 'City suffix (e.g. -ville)',
            'streetSuffix' => 'Street suffix (e.g. Ave, Blvd)',

            // 📧 Phone & Email
            'phoneNumber' => 'Phone number',
            'e164PhoneNumber' => 'E.164 format phone number',
            'email' => 'Random email address',
            'safeEmail' => 'Safe email (example.org)',
            'freeEmail' => 'Free email (gmail/yahoo/etc)',
            'companyEmail' => 'Company-style email address',
            'userName' => 'Username',
            'domainName' => 'Domain name',
            'domainWord' => 'Domain keyword (e.g. example)',
            'tld' => 'Top-level domain (e.g. com)',

            // 🌐 Internet
            'url' => 'Website URL',
            'ipv4' => 'IPv4 address',
            'ipv6' => 'IPv6 address',
            'macAddress' => 'MAC address',
            'slug' => 'URL-friendly slug',

            // 🏢 Company
            'company' => 'Company name',
            'jobTitle' => 'Job title',
            'catchPhrase' => 'Catch phrase (business buzzwords)',
            'bs' => 'Company slogan (business jargon)',
            'companySuffix' => 'Company suffix (Inc., LLC)',

            // 💳 Payment
            'creditCardNumber' => 'Credit card number',
            'creditCardType' => 'Credit card type (Visa, Mastercard, etc)',
            'creditCardExpirationDate' => 'Card expiration date',
            'iban' => 'International bank account number',
            'swiftBicNumber' => 'SWIFT/BIC bank code',

            // 🕒 Date & Time
            'date' => 'Random date',
            'time' => 'Random time',
            'dateTime' => 'DateTime object',
            'unixTime' => 'Unix timestamp',
            'dateTimeThisCentury' => 'DateTime in this century',
            'dateTimeThisDecade' => 'DateTime in this decade',
            'dateTimeThisYear' => 'DateTime in this year',
            'dayOfMonth' => 'Day of month',
            'dayOfWeek' => 'Day of week',
            'month' => 'Month (number)',
            'monthName' => 'Month name',
            'year' => 'Year',

            // 📝 Text / Lorem
            'word' => 'Single word',
            'words' => 'Array of words',
            'sentence' => 'Single sentence',
            'sentences' => 'Array of sentences',
            'paragraph' => 'Single paragraph',
            'paragraphs' => 'Array of paragraphs',
            'text' => 'Random text (multi-paragraph)',

            // 🔢 Numbers & Misc
            'randomDigit' => 'Single digit (0-9)',
            'randomDigitNotNull' => 'Non-zero digit (1-9)',
            'randomNumber' => 'Random number',
            'numberBetween' => 'Random number between two values',
            'randomFloat' => 'Random float number',
            'boolean' => 'True/false boolean',
            'uuid' => 'UUID (unique ID)',
            'sha256' => 'SHA-256 hash',
            'md5' => 'MD5 hash',
            'regexify' => 'Custom string using regex pattern',
            'bothify' => 'String with ? and # replaced by letters/numbers',
            'hexColor' => 'Hex color code (e.g. #ff00aa)',
            'rgbColor' => 'RGB color value',
            'rgbCssColor' => 'CSS-style rgb color (e.g. rgb(255,0,0))',
            'colorName' => 'Color name (e.g. red, blue)',
            'locale' => 'Locale code (e.g. en_US)',

            // 🧪 System
            'fileExtension' => 'File extension (e.g. txt, jpg)',
            'mimeType' => 'MIME type (e.g. image/png)',
            'fileName' => 'File name',
            'filePath' => 'File path',
            'unixTime' => 'Unix timestamp',
            'languageCode' => 'Language code (e.g. en)',
            'currencyCode' => 'Currency code (e.g. USD)',
        ];

        echo "\nMapping options: \n";
        foreach ($fakerMethods as $key=>$fakerMethod) {
            echo "[$key] - $fakerMethod\n";
        }

        echo PHP_EOL.PHP_EOL;

        echo "Please map the fields below".PHP_EOL.PHP_EOL;
        foreach ($fields as $field) {
            $populate_mapper[$field] = readline("Field[$field]: What data do you want to populate? ");
        }
        $faker->setFillableFields($populate_mapper);

        $username = readline("Who are you? give username: ");
        if (empty($username)) {
            echo "Username can't be empty".PHP_EOL.PHP_EOL;
            return;
        }

        $user = \Simp\Core\modules\user\entity\User::loadByName($username);

        if (empty($user)) {
            echo "Username can't be empty".PHP_EOL.PHP_EOL;
            return;
        }

        $total_to_generate = readline("Total to generate: ");
        if (is_numeric($total_to_generate)) {
            $faker->populateData([
                'uid' => $user->getUid(),
                'lang' => 'en',
                'bundle' => $content_type,
                'status' => 1
            ], (int) $total_to_generate);
        }
        else {
            $faker->populateData([
                'uid' => $user->getUid(),
                'lang' => 'en',
                'bundle' => $content_type,
                'status' => 1
            ],1);
            $total_to_generate = 1;
        }

        $faker->save();
        echo "\033[" . $terminal_colors['green'] . "m operation run successfully (Node created count $total_to_generate) \033[0m\n";
    }

    function faker_user_create()
    {
        global $terminal_colors;
        $total_to_generate = readline("Total Users to generate: ");
        if (is_numeric($total_to_generate)) {
            $faker = new \Simp\Core\modules\user\faker_manager\FakerManager();
            $faker->populateData([], $total_to_generate);
            $faker->save();
            echo "\033[" . $terminal_colors['green'] . "m operation run successfully (Users created count $total_to_generate) \033[0m\n";
        }
    }

    $found();
}

function set_up_database(array $options)
{
    global $terminal_colors;
    echo "\nSetting up database\n";

    if (count($options) !== 1) {

        echo "\033[" . $terminal_colors['red'] . "m exact one option is required" . PHP_EOL;
        echo PHP_EOL;
        echo "--option first expected values eg 
        --setup,
        --status,
        --info,
        " .PHP_EOL.PHP_EOL;
        echo "\033[0m";
        return;
    }

    $supported = [
        '--setup' => 'setup',
        '--status' => 'status',
        '--info' => 'info',
    ];

    $found = $supported[$options[0]] ?? null;
    if (empty($found)) {
        echo "\033[" . $terminal_colors['red'] . "m option not supported.\033[0m\n";
        return;
    }

    function setup(): void
    {
        global $terminal_colors;
        $host = readline("Database host: ");
        if (empty($host)) {
            echo "Host can't be empty".PHP_EOL.PHP_EOL;
            return;
        }
        $username = readline("Database username: ");
        if (empty($username)) {
            echo "Username can't be empty".PHP_EOL.PHP_EOL;
            return;
        }
        $password = readline("Database password: ");
        if (empty($password)) {
            echo "Password can't be empty".PHP_EOL.PHP_EOL;
            return;
        }
        $database_name = readline("Database name: ");
        if (empty($database_name)) {
            echo "Database name can't be empty".PHP_EOL.PHP_EOL;
            return;
        }
        $port = readline("Database port: ");
        if (empty($port)) {
            $port = 3306;
        }

        $database = [
            "hostname" => $host,
            "username" => $username,
            "password" => $password,
            "port" => $port,
            'dbname' => $database_name,
        ];

        $schema = Caching::init()->get('default.admin.database');
        if (file_exists($schema)) {
            $schema_data = Yaml::parseFile($schema);
            $schema_data = array_merge($schema_data, $database);
            $system = new SystemDirectory();
            $setting_data = $system->setting_dir . DIRECTORY_SEPARATOR . 'database' .
                DIRECTORY_SEPARATOR . 'database.yml';
            if (file_put_contents($setting_data, Yaml::dump($schema_data))) {
                echo "\033[" . $terminal_colors['green'] . "m Database setting done successfully.\033[0m\n";
                return;
            }
            echo "\033[" . $terminal_colors['red'] . "m Database setting failed.\033[0m\n";
            return;
        }
        echo "\033[" . $terminal_colors['yellow'] . "m sorry failed to locate database schema file.\033[0m\n";
    }

    $found();

}

return [
    'cleaner' => "cache_clear",
    'user' => "user",
    'faker' => "faker",
    'database' => "set_up_database",
];