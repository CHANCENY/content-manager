<?php

namespace Simp\Core\modules\structures\views;

class ViewDataObject
{
    protected array $data = [];
    public function __construct(array $data)
    {
        foreach ($data as $key=>$row) {
            if (isset($this->data[$key])) {
                $this->data[$key] = array_merge($this->data[$key], $row[$key]);
            }else {
                $this->data[$key] = $row;
            }
        }
    }
}