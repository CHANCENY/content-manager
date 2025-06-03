<?php

namespace Simp\Core\extends\auto_path\src\path;

use Exception;
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

    protected function validatePath(string $path, int $nid, int $pattern_id): bool
    {
        $query = "SELECT id FROM auto_path WHERE path = :path AND nid = :nid AND pattern_id = :pattern_id";
        $query = $this->database->con()->prepare($query);
        $query->bindValue(':path', $path);
        $query->bindValue(':nid', $nid);
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

            while (true) {
                $list = explode('/', $pattern);
                foreach ($list as $key=>$token) {

                    if (str_starts_with($token, '[') && str_ends_with($pattern, ']')) {
                        $appended = 0;
                        while (true) {
                            $token = $token_manager->resolver($token, ['node' => $node]);
                            $token_url_part = $this->createAliasUrl($token);
                            if ($appended !== 0) {
                                $token_url_part .= "-".$appended;
                            }
                            if (!$this->validatePath($token_url_part, $node->getNid(), $data['id'])) {
                                $list[$key] = $token_url_part;
                                break;
                            }
                            $appended++;
                        }
                    }
                }

                $temp = implode('/', $list);
                $temp = "/" . trim($temp, '/');
                if (!$this->validatePath($temp, $node->getNid(), $data['id'])) {
                    $pattern = $temp;
                    break;
                }
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

}