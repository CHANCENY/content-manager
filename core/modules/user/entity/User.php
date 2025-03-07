<?php

namespace Simp\Core\modules\user\entity;

use PDO;
use Simp\Core\modules\database\Database;
use Simp\Core\modules\user\profiles\Profile;
use Simp\Core\modules\user\roles\Role;
use Simp\Core\modules\user\trait\StaticHelperTrait;

class User
{
    use StaticHelperTrait;
    public function __construct(protected ?int $uid, protected ?string $name, protected ?string $mail,
                                protected ?string $password, protected ?string $created, protected ?string $updated,
                                protected ?string $login, protected bool|int|null $status){}

    public static function load(int $uid): ?User
    {
        $query = "SELECT * FROM `users` WHERE `uid` = :uid";
        $query = Database::database()->con()->prepare($query);
        $query->bindParam(':uid', $uid, PDO::PARAM_INT);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        if (empty($result)) {
            return null;
        }
        return new User(...$result);
    }

    public static function loadByMail(string $mail): ?User
    {
        $query = "SELECT * FROM `users` WHERE `mail` = :mail";
        $query = Database::database()->con()->prepare($query);
        $query->bindParam(':mail', $mail, PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        if (empty($result)) {
            return null;
        }
        return new User(...$result);
    }

    public static function loadByName(string $name): ?User
    {
        $query = "SELECT * FROM `users` WHERE `name` = :name";
        $query = Database::database()->con()->prepare($query);
        $query->bindParam(':name', $name, PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        if (empty($result)) {
            return null;
        }
        return new User(...$result);
    }

    /**
     * @param array $data
     * @return User|false|null
     * False is returned if name or mail already exist.
     * Null if keys name, mail, password, time_zone are not set
     */
    public static function create(array $data): User|null|false
    {
        $query = "INSERT INTO users (name, mail, password, status) VALUES (:name, :mail, :password, :status)";
        $query_profile = "INSERT INTO user_profile (uid, time_zone) VALUES (:uid, :time_zone)";
        $query_role = "INSERT INTO user_roles (name, role_name, role_label, uid) VALUES (:name, :role_name, :role_label, :uid)";
        $connection = Database::database()->con();

        // Creation user first so that we can have uid value.
        $uid = 0;
        if (!empty($data['name']) && !empty($data['mail']) && !empty($data['password']) && !empty($data['time_zone'])) {

            // Hash password
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);

            // checking the if email or name exist already.
            if (self::loadByMail($data['mail']) !== null || self::loadByName($data['name']) !== null) {
                return false;
            }

            $data['status'] = 1;
            $statement = $connection->prepare($query);
            $statement->bindParam(':name', $data['name'], PDO::PARAM_STR);
            $statement->bindParam(':mail', $data['mail'], PDO::PARAM_STR);
            $statement->bindParam(':password', $data['password'], PDO::PARAM_STR);
            $statement->bindParam(':status', $data['status'], PDO::PARAM_INT);
            $statement->execute();
            $uid = $connection->lastInsertId();
        }

        // If uid is created then lets create the profile and roles.
        if (!empty($uid)) {

            // Check if roles are set in array
            if (!empty($data['roles']) && is_array($data['roles'])) {
                foreach ($data['roles'] as $role) {
                    $statement = $connection->prepare($query_role);
                    $statement->bindParam(':uid', $uid, PDO::PARAM_INT);
                    $statement->bindParam(':role_name', $role, PDO::PARAM_STR);
                    $statement->bindParam(':role_label', $role, PDO::PARAM_STR);
                    $statement->bindParam(':name', $role, PDO::PARAM_STR);
                    $statement->execute();
                }
            }
            elseif (!empty($data['roles']) && is_string($data['roles'])) {
                $role = $data['roles'];
                $statement = $connection->prepare($query_role);
                $statement->bindParam(':uid', $uid, PDO::PARAM_INT);
                $statement->bindParam(':role_name', $role, PDO::PARAM_STR);
                $statement->bindParam(':role_label', $role, PDO::PARAM_STR);
                $statement->bindParam(':name', $role, PDO::PARAM_STR);
                $statement->execute();
            }
            else {
                $role = "authenticated";
                $statement = $connection->prepare($query_role);
                $statement->bindParam(':uid', $uid, PDO::PARAM_INT);
                $statement->bindParam(':role_name', $role, PDO::PARAM_STR);
                $statement->bindParam(':role_label', $role, PDO::PARAM_STR);
                $statement->bindParam(':name', $role, PDO::PARAM_STR);
                $statement->execute();
            }

            // Lets create profile.
            $statement = $connection->prepare($query_profile);
            $statement->bindParam(':uid', $uid, PDO::PARAM_INT);
            $statement->bindParam(':time_zone', $data['time_zone'], PDO::PARAM_STR);
            $statement->execute();
            return self::load($uid);
        }
        return null;
    }

    public function getRoles(): array
    {
        $query = "SELECT * FROM `user_roles` WHERE `uid` = :uid";
        $query = Database::database()->con()->prepare($query);
        $query->bindParam(':uid', $this->uid, PDO::PARAM_INT);
        $query->execute();
        $result = $query->fetchAll(PDO::FETCH_ASSOC);
        if (empty($result)) {
            return [];
        }
        return array_map(fn($item) => new Role(...$item), $result);
    }

    public function getProfile(): ?Profile
    {
        $query = "SELECT * FROM `user_profile` WHERE `uid` = :uid";
        $query = Database::database()->con()->prepare($query);
        $query->bindParam(':uid', $this->uid, PDO::PARAM_INT);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        if (empty($result)) {
            return null;
        }
        return new Profile(...$result);
    }

    public function getUid(): ?int
    {
        return $this->uid;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getCreated(): ?string
    {
        return $this->created;
    }

    public function getUpdated(): ?string
    {
        return $this->updated;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function getStatus(): ?bool
    {
        return $this->status;
    }

    public function __get(string $name)
    {
        return $this->$name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function setMail(?string $mail): void
    {
        $this->mail = $mail;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function setLogin(?string $login): void
    {
        $this->login = $login;
    }

    public function setStatus(?bool $status): void
    {
        $this->status = $status;
    }

    public function update(): bool
    {
        $query = "UPDATE `users` SET mail = :mail, name = :name, password = :password, status = :status, login = :login WHERE uid = :uid";
        $query = Database::database()->con()->prepare($query);
        $query->bindParam(':mail', $this->mail, PDO::PARAM_STR);
        $query->bindParam(':name', $this->name, PDO::PARAM_STR);
        $query->bindParam(':password', $this->password, PDO::PARAM_STR);
        $query->bindParam(':status', $this->status, PDO::PARAM_INT);
        $query->bindParam(':uid', $this->uid, PDO::PARAM_INT);
        $query->bindParam(':login', $this->login, PDO::PARAM_STR);
        return $query->execute();
    }
}