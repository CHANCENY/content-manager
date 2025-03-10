<?php

namespace Simp\Core\modules\files\entity;

use Simp\Core\modules\database\Database;

class File
{
    public function __construct(protected int $fid, protected int $uid, protected string $uri, protected string $mime_type,
                                protected int $size, protected string $extension, protected string $name, protected string $created){}

    public static function create(array $data): ?static
    {
        if (!empty($data['name']) && !empty($data['uri']) && !empty($data['mime_type']) && !empty($data['size']) && !empty($data['extension']) && !empty($data['uid'])) {
            $query = "INSERT INTO `file_managed` (`name`, `uri`, `mime_type`, `size`, `extension`,`uid`) VALUES (:name, :uri, :mime_type, :size, :extension, :uid)";
            $con = Database::database()->con();
            $query = $con->prepare($query);
            $query->bindParam(':name', $data['name']);
            $query->bindParam(':uri', $data['uri']);
            $query->bindParam(':mime_type', $data['mime_type']);
            $query->bindParam(':size', $data['size']);
            $query->bindParam(':extension', $data['extension']);
            $query->bindParam(':uid', $data['uid']);
            $query->execute();

            $fid = $con->lastInsertId();
            $query = "SELECT * FROM `file_managed` WHERE `fid` = :id AND `uid` = :uid";
            $con = Database::database()->con();
            $query = $con->prepare($query);
            $query->bindParam(':id', $fid);
            $query->bindParam(':uid', $data['uid']);
            $query->execute();
            $files = $query->fetch();
            return new static(...$files);
        }
        return null;
    }

    public function getFid(): int
    {
        return $this->fid;
    }

    public function setFid(int $fid): void
    {
        $this->fid = $fid;
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function setUid(int $uid): void
    {
        $this->uid = $uid;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $uri): void
    {
        $this->uri = $uri;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCreated(): string
    {
        return $this->created;
    }

    public function setCreated(string $created): void
    {
        $this->created = $created;
    }

    public function getMimeType(): string
    {
        return $this->mime_type;
    }

    public function setMimeType(string $mime_type): void
    {
        $this->mime_type = $mime_type;
    }



    public static function load(int $fid): ?static
    {
        $con = Database::database()->con();
        $query = "SELECT * FROM `file_managed` WHERE `fid` = :id";
        $query = $con->prepare($query);
        $query->bindParam(':id', $fid);
        $query->execute();
        $files = $query->fetch();
        if (empty($files)) {
            return null;
        }
        return new static(...$files);
    }

    public static function loadByUid(int $uid): array
    {
        $con = Database::database()->con();
        $query = "SELECT * FROM `file_managed` WHERE `uid` = :uid";
        $query = $con->prepare($query);
        $query->bindParam(':uid', $uid);
        $query->execute();
        $files = $query->fetchAll();
        if (empty($files)) {
            return [];
        }
        return array_map(fn($file) => new static(...$file), $files);
    }

    public function delete(): bool
    {
        $con = Database::database()->con();
        $query = "DELETE FROM `file_managed` WHERE `fid` = :id";
        $query = $con->prepare($query);
        $query->bindParam(':id', $this->fid);
        return $query->execute();
    }

    public function update(): bool
    {
        $con = Database::database()->con();
        $query = "UPDATE `file_managed` SET `uri` = :uri, name = :name, mime_type = :mime_type, `size` = :size, `extension` = :extension WHERE `fid` = :id";
        $query = $con->prepare($query);
        $query->bindParam(':uri', $this->uri);
        $query->bindParam(':name', $this->name);
        $query->bindParam(':size', $this->size);
        $query->bindParam(':extension', $this->extension);
        $query->bindParam(':mime_type', $this->mime_type);
        $query->bindParam(':id', $this->fid);
        return $query->execute();
    }

}