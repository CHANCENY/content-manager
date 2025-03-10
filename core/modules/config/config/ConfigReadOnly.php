<?php

namespace Simp\Core\modules\config\config;

class ConfigReadOnly
{
    public function __construct(protected readonly string $name, protected readonly mixed $data)
    {
    }

    /**
     * @param string|null $name You can use dot notation. Or null if data being search is just string or number
     * @param mixed|null $default
     * @return mixed
     */
    public function get(?string $name = null, mixed $default = null): mixed
    {
        $config = [];
        if (is_array($this->data)) {
            $config = $this->data;
        }
        elseif (is_object($this->data)) {
            $config = json_decode(json_encode($this->data), true);
        }else {
            return $this->data;
        }

        foreach ($config as $key => $value) {
            if ($key === $name) {
                return $value;
            }
            if (is_array($value)) {
                $value = $this->recursive_search($value, $name);
                if (!empty($value)) {
                    return $value;
                }
            }
        }
        return $default;
    }

    private function recursive_search(array $array, string $search)
    {
        foreach ($array as $key => $value) {
            if ($key === $search) {
                return $value;
            }
            if (is_array($value)) {
                return $this->recursive_search($value, $search);
            }
        }
        return null;
    }
}