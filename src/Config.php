<?php

namespace LowerSpeck;

class Config
{
    private $data;

    public function __construct(string $filepath)
    {
        if (file_exists($filepath)) {
            $this->data = json_decode(file_get_contents($filepath));
        }
        if (!$this->data) {
            $this->data = (object)[];
        }
        if (!isset($this->data->paths) || !$this->data->paths || !is_array($this->data->paths)) {
            $this->data->paths = ['.'];
        }
    }

    public function __call(string $method, array $params)
    {
        if (!is_object($this->data)) {
            throw new \Exception(gettype($this->data));
        }
        return $this->data->$method;
    }
}
