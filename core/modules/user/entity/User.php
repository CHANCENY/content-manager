<?php

namespace Simp\Core\modules\user\entity;

class User
{
    public function __construct(
        protected ?int $uid,
        protected ?string $name,
        protected ?string $mail,
        protected ?string $password,
        protected ?string $created,
        protected ?string $updated,
        protected ?string $login,
        protected ?bool $status,
    )
    {
    }

    public function __get(string $name)
    {
       return $this->$name ?? null;
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMail(): string
    {
        return $this->mail;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getCreated(): string
    {
        return $this->created;
    }

    public function getUpdated(): string
    {
        return $this->updated;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function isStatus(): bool
    {
        return $this->status;
    }


}