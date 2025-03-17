<?php

namespace Simp\Core\modules\structures\content_types\entity;

use PDO;
use Simp\Core\modules\database\Database;
use Simp\Core\modules\structures\content_types\ContentDefinitionManager;
use Simp\Core\modules\structures\content_types\storage\ContentDefinitionStorage;
use Simp\Core\modules\user\entity\User;
use Simp\Core\modules\user\trait\StaticHelperTrait;

class Node
{
    use StaticHelperTrait;

    protected ?array $entity_types = [];
    protected array $values = [];

    public function __construct(
        protected ?int $nid,
        protected ?string $title,
        protected ?string $bundle,
        protected ?string $lang,
        protected ?int $status,
        protected ?string $created,
        protected ?string $updated,
        protected ?int $uid,
    )
    {
        $this->entity_types = ContentDefinitionManager::contentDefinitionManager()->getContentType($this->bundle) ?? [];
        $storage = ContentDefinitionStorage::contentDefinitionStorage($this->bundle)->getStorageJoinStatement($this->nid);
        $query = Database::database()->con()->prepare($storage);
        $query->bindValue(':nid', $this->nid);
        $query->execute();
        $data = $query->fetchAll();
        $rows = [];

        foreach($data as $key=>$value) {
             if(is_array($value)) {
                foreach($value as $k=>$v) {
                    $rows[$k]['value'][] = $v;
                    $rows[$k]['value'] = array_unique($rows[$k]['value']);
                }
             }
        }
        $this->values = $rows;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getBundle(): ?string
    {
        return $this->bundle;
    }

    public function setBundle(?string $bundle): void
    {
        $this->bundle = $bundle;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }

    public function setLang(?string $lang): void
    {
        $this->lang = $lang;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): void
    {
        $this->status = $status;
    }

    public function getCreated(): ?string
    {
        return $this->created;
    }

    public function setCreated(string $created): void
    {
        $this->created = $created;
    }

    public function getUpdated(): ?string
    {
        return $this->updated;
    }

    public function setUpdated(int $updated): void
    {
        $this->updated = $updated;
    }

    public function getNid(): ?int
    {
        return $this->nid;
    }

    public function setNid(?int $nid): void
    {
        $this->nid = $nid;
    }

    public static function create(array $data): ?Node
    {
        if (!empty($data['title']) && !empty($data['bundle']) && !empty($data['uid'])) {
            $data['lang'] = !empty($data['lang']) ? $data['lang'] : 'en';
            $connection = Database::database()->con();
            $query = "INSERT INTO node_data (title, bundle, status, uid, lang) VALUES (:title, :bundle, :status, :uid, :lang)";
            $query = $connection->prepare($query);
            $query->bindValue(':title', $data['title']);
            $query->bindValue(':bundle', $data['bundle']);
            $query->bindValue(':status', $data['status'] ?? 0);
            $query->bindValue(':uid', $data['uid']);
            $query->bindValue(':lang', $data['lang']);
            $query->execute();
            $nid = $connection->lastInsertId();
            if (!empty($nid)) {
                $node = Node::load($nid);
                // Add more field data.
                foreach ($data as $key => $value) {
                    $node->addFieldData($key, $value);
                }
                return Node::load($nid);
            }
        }
        return null;
    }

    public function __get(string $name)
    {
        return $this->$name;
    }

    public function getUid(): ?int
    {
        return $this->uid;
    }

    public function setUid(?int $uid): void
    {
        $this->uid = $uid;
    }

    public function getOwner(): ?User
    {
        return User::load($this->uid);
    }

    public function addFieldData(string $field_name,  $values): bool
    {
        $storage_query = ContentDefinitionStorage::contentDefinitionStorage($this->bundle)
        ->getStorageInsertStatement($field_name);

        if (!empty($storage_query)) {
            if (!is_array($values)) {
                $values = [$values];
            }
            $flags = [];
            foreach ($values as $value) {

                $query = Database::database()->con()->prepare($storage_query);
                $query->bindParam(':nid', $this->nid);
                $query->bindParam(':field_value', $value);
                $flags[]= $query->execute();
            }
            return !in_array(false, $flags);

        }
        return false;
    }

    public static function load(int $nid): ?Node {
        $connection = Database::database()->con();
        $query = "SELECT * FROM node_data WHERE nid = :nid";
        $query = $connection->prepare($query);
        $query->bindValue(':nid', $nid);
        $query->execute();
        $result = $query->fetch();
        if (empty($result)) {
            return null;
        }
        return new Node(...$result);
    }
}