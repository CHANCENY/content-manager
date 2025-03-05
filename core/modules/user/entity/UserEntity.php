<?php

namespace Simp\Core\modules\user\entity;

use Simp\Core\modules\database\Database;
use Simp\Core\modules\timezone\TimeZone;
use Simp\Core\modules\user\roles\RoleManager;
use stdClass;
use Symfony\Component\Yaml\Yaml;

class UserEntity extends TimeZone
{
    private array $entity_form;

    protected ?User $user;

    protected TimeZone $timezone;
    public function __construct(int $uid = 0)
    {
        parent::__construct();
        $this->user = null;

        $account_form = $this->setting_dir . DIRECTORY_SEPARATOR . 'defaults' .
            DIRECTORY_SEPARATOR . 'forms' . DIRECTORY_SEPARATOR . 'user.entity.form.yml';
        $this->timezone = new TimeZone();
        if (file_exists($account_form)) {
            $this->entity_form = Yaml::parse(file_get_contents($account_form));
            $list = $this->timezone->getSimplifiedTimezone();
            sort($list);
            $this->entity_form['fields']['user_prefer_timezone']['option_values'] = $list;
        }

//        if (!empty($uid)) {
//            $query = "SELECT * FROM `users` WHERE `uid` = :user_id";
//            $statement = Database::database()->con()->prepare($query);
//            $statement->bindValue(':user_id', $uid);
//            $statement->execute();
//            $this->user = $statement->fetchObject(User::class);
//            if (!empty($this->entity_form['fields'])) {
//                foreach ($this->entity_form['fields'] as $key => $value) {
//                    if (!empty($value['name']) && $value['name'] !== 'password') {
//                        $name = $value['name'];
//                        $this->entity_form['fields'][$key]['default_value'] = $this->user->$name ?? null;
//                    }
//                }
//            }
//            if ($this->user->getUid()) {
//                $role_manager = new RoleManager($this->user->getUid());
//            }
//        }
    }

    public function getEntityForm(): array {
        return $this->entity_form;
    }

    public function getEntityFormFields(): array
    {
        return $this->entity_form['fields'] ?? [];
    }

    public function verifyAccountAlreadyExist(string $email, string $name = ''): bool
    {
        $query_email = "SELECT uid FROM `users` WHERE mail = :email";
        $query_name = "SELECT uid FROM `users` WHERE name = :name";
        $con = Database::database()->con();
        $statement = $con->prepare($query_email);
        $statement->bindValue(':email', $email);
        $statement->execute();
        $result = $statement->fetch();
        if (!empty($result)) {
            return true;
        }
        $statement = $con->prepare($query_name);
        $statement->bindValue(':name', $name);
        $statement->execute();
        $result = $statement->fetch();
        if (!empty($result)) {
            return true;
        }
        return false;
    }

    public function addAccount(array $data): bool
    {
        if (!empty($data['mail']) && !empty($data['password']) && !empty($data['name']) && !$this->verifyAccountAlreadyExist($data['mail'], $data['name'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);

            $query = "INSERT INTO `users` (`name`, `mail`, `password`, `status`) VALUES (:name, :mail, :password, :status)";
            $role_query = "INSERT INTO `user_roles` (`name`, `role_name`, `role_label`, `uid`) VALUES (:name, :role_name, :role_label, :uid)";
            $profile_query = "INSERT INTO `user_profile` (uid) VALUES (:uid)";

            $con = Database::database()->con();
            // Start account creation.
            $stmt = $con->prepare($query);
            $stmt->bindValue(':name', $data['name']);
            $stmt->bindValue(':mail', $data['mail']);
            $stmt->bindValue(':password', $data['password']);
            $stmt->bindValue(':status', 1);
            $stmt->execute();
            $uid = $con->lastInsertId();

            // Add Roles
            $stmt = $con->prepare($role_query);
            $stmt->bindValue(':uid', $uid);
            foreach ($data['roles'] as $role) {
                if (is_array($role)) {
                    if (!empty($role['label']) && !empty($role['name'])) {
                        $stmt->bindValue(':role_name', $role['name']);
                        $stmt->bindValue(':role_label', $role['label']);
                        $stmt->bindValue(':name', $role['name']);
                    }
                }
                elseif (is_string($role)) {
                    $stmt->bindValue(':role_name', $role);
                    $stmt->bindValue(':role_label', $role);
                    $stmt->bindValue(':name', $role);
                }
            }
            $stmt->execute();

            // Add profile
            $stmt = $con->prepare($profile_query);
            $stmt->bindValue(':uid', $uid);
            $stmt->execute();
            return true;

        }
        return false;
    }
}