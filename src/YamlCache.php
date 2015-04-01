<?php

/**
 * This file is part of modHelper
 *
 * (c) Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE
 */
use Symfony\Component\Yaml\Yaml;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
class YamlCache
{

    /**
     * @type string
     */
    private $file;

    /**
     * @type array
     */
    private $cache;

    public function __construct($directory, $namespace)
    {
        $this->file  = rtrim($directory, '/').'/'.$namespace.'.yaml';

        $this->ensureFileExists();
        $this->cache = Yaml::parse(file_get_contents($this->file));
    }

    /**
     * @param string|int $key
     * @param int        $expiration
     *
     * @return bool
     */
    public function has($key, $expiration = 0)
    {
        if (!array_key_exists($key, $this->cache)) {
            return false;
        }

        $item = $this->cache[$key];
        if ($expiration !== 0 && $item['timestamp'] + $expiration < time()) {
            $this->remove($key);

            return false;
        }

        return true;
    }

    public function set($key, $value)
    {
        $this->cache[$key] = [
            'timestamp' => time(),
            'value'     => $value
        ];

        $this->saveToFile();

        return $value;
    }

    /**
     * @param string|int $key
     * @param int        $expiration
     *
     * @return mixed
     */
    public function get($key, $expiration = 0)
    {
        if (!$this->has($key, $expiration)) {
            return null;
        }

        return $this->cache[$key]['value'];
    }

    public function remove($key)
    {
        unset($this->cache[$key]);
        $this->saveToFile();
    }

    private function ensureFileExists()
    {
        if (!file_exists($this->file)) {
            $this->cache = [];
            $this->saveToFile();
        }
    }

    private function saveToFile()
    {
        file_put_contents($this->file, Yaml::dump($this->cache, 8, 4));
    }
}
