<?php

namespace Simp\Core\extends\auto_path\src\path;

use Exception;
use Google\Service\Compute\Router;
use Simp\Core\lib\controllers\SystemController;
use Simp\Core\lib\memory\cache\Caching;
use Simp\Core\lib\routes\Route;
use Simp\Core\modules\database\Database;
use Simp\Core\modules\structures\content_types\entity\Node;
use Simp\Core\modules\tokens\TokenManager;

class AutoPathAlias
{
    public function __construct(protected Database $database)
    {
    }

    protected function validateAlias(string $alias, string $entity_type): bool
    {
        $query = "SELECT * FROM auto_path_patterns WHERE pattern_path = :p AND entity_type = :entity_type";
        $query = $this->database->con()->prepare($query);
        $query->bindValue(':p', $alias);
        $query->bindValue(':entity_type', $entity_type);
        $query->execute();
        $count = $query->fetchColumn();
        return !empty($count);
    }

    public function addAlias(string $pattern, string $entity_type): bool
    {
        if (!$this->validateAlias($pattern, $entity_type)) {
            $query = "INSERT INTO auto_path_patterns (`pattern_path`, `entity_type`) VALUES (:pattern_path, :entity_type)";
            $query = $this->database->con()->prepare($query);
            $query->bindValue(':pattern_path', $pattern);
            $query->bindValue(':entity_type', $entity_type);
            return $query->execute();
        }
        return false;
    }

    public function getAliasByEntityType(string $entity_type): array|false|null
    {
        $query = "SELECT * FROM auto_path_patterns WHERE entity_type = :entity_type";
        $query = $this->database->con()->prepare($query);
        $query->bindValue(':entity_type', $entity_type);
        $query->execute();
        return $query->fetch();

    }

    public function getAliasByPattern(string $pattern): array|false|null
    {
        $query = "SELECT * FROM auto_path_patterns WHERE pattern_path = :pattern";
        $query = $this->database->con()->prepare($query);
        $query->bindValue(':pattern', $pattern);
        $query->execute();
        return $query->fetch();
    }

    public function deleteAlias(int $id): bool
    {
        $query = "DELETE FROM auto_path_patterns WHERE id = :id";
        $query = $this->database->con()->prepare($query);
        $query->bindValue(':id', $id);
        return $query->execute();
    }

    public function listAliases(): array
    {
        $query = "SELECT * FROM auto_path_patterns";
        $query = $this->database->con()->prepare($query);
        $query->execute();
        return $query->fetchAll();
    }

    protected function validatePath(string $path,int $pattern_id): bool
    {
        $query = "SELECT id FROM auto_path WHERE path = :path AND pattern_id = :pattern_id";
        $query = $this->database->con()->prepare($query);
        $query->bindValue(':path', $path);
        $query->bindValue(':pattern_id', $pattern_id);
        $query->execute();
        $count = $query->fetchColumn();
        return !empty($count);
    }

    protected function createAliasUrl(string $token): string
    {
        // Trim whitespace
        $token = trim($token);

        // Convert to lowercase (optional but recommended for URLs)
        $token = strtolower($token);

        // Replace all non-alphanumeric characters with a dash
        $token = preg_replace('/[^a-z0-9]+/i', '-', $token);

        // Replace multiple dashes with a single dash
        $token = preg_replace('/-+/', '-', $token);

        // Remove starting and trailing dashes
        return trim($token, '-');
    }

    /**
     * @throws Exception
     */
    public function create(Node $node): bool
    {
        $token_manager = TokenManager::token();
        $data = $this->getAliasByEntityType($node->getEntityArray()['machine_name']) ?? [];
        if ($data) {
            $pattern = $data['pattern_path'];

            $appended = 0;
            while (true) {
                $list = explode('/', $pattern);
                foreach ($list as $key=>$token) {

                    if (str_starts_with($token, '[') && str_ends_with($pattern, ']')) {
                        while (true) {
                            $token = $token_manager->resolver($token, ['node' => $node]);
                            $token_url_part = $this->createAliasUrl($token);
                            if ($appended !== 0) {
                                $token_url_part .= "-".$appended;
                            }
                            if (!$this->validatePath($token_url_part, $data['id'])) {
                                $list[$key] = $token_url_part;
                                break;
                            }
                            $appended++;
                        }
                    }
                }

                $temp = implode('/', $list);
                $temp = "/" . trim($temp, '/');
                if (!$this->validatePath($temp, $data['id'])) {
                    $pattern = $temp;
                    break;
                }
                $appended += 1;
            }

            $query = "INSERT INTO auto_path (path, nid, pattern_id) VALUES (:path, :nid, :pattern_id)";
            $query = $this->database->con()->prepare($query);
            $query->bindValue(':path', $pattern);
            $query->bindValue(':nid', $node->getNid());
            $query->bindValue(':pattern_id', $data['id']);
            return $query->execute();
        }
        return false;
    }

    public static function createRouteId(int $path_id): string
    {
        $words = \NumberFormatter::create('en_US', \NumberFormatter::SPELLOUT)->format($path_id);
        $words = "auto.path.route.{$words}";

        // Trim whitespace
        $token = trim($words);

        // Convert to lowercase (optional but recommended for URLs)
        $token = strtolower($token);

        // Replace all non-alphanumeric characters with a dash
        $token = preg_replace('/[^a-z0-9]+/i', '.', $token);

        // Remove starting and trailing dashes
        return trim($token, '.');
    }

    public function isEntityTypeAutoPathEnabled(string $entity_type): bool
    {
        $pattern = $this->getAliasByEntityType($entity_type);
        return !empty($pattern);
    }

    public static function factory(?Database $database = null): AutoPathAlias
    {
        if (is_null($database)) {
            $database = Database::database();
        }
        return new self($database);
    }

    public  function getPaths(): array
    {
        $query = $this->database->con()->prepare("SELECT * FROM auto_path");
        $query->execute();
        $results = $query->fetchAll();
        $routes = [];
        foreach ($results as $result) {
            $route_key = self::createRouteId($result['nid']);
            $route = [
                'title' => 'Alias',
                'path' => $result['path'],
                'method' => [
                    'GET',
                    'POST',
                ],
                'controller' => [
                    'class' => SystemController::class,
                    'method' => 'content_node_controller'
                ],
                'access' => [
                    'administrator',
                    'content_creator',
                    'authenticated',
                    'manager'
                ],
                'options' => [
                    'node' => $result['nid'],
                ]
            ];
            $routes[$route_key] = new Route($route_key, $route);
        }
        return $routes;
    }

    public static function injectAliases(): array
    {
        return self::factory()->getPaths();
    }
}