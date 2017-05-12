<?php

namespace SwagEssentials\Common;

class RedisFactory
{
    public static function factory($configs)
    {
        $redis = new \Redis();

        foreach ($configs as $config) {
            $persistent = isset($config['persistent']) ? $config['persistent'] : 1;
            $port = isset($config['port']) ? $config['port'] : 6379;
            $timeout = isset($config['timeout']) ? $config['timeout'] : 30;
            $index = isset($config['dbindex']) ? $config['dbindex'] : 0;
            $auth = isset($config['auth']) ? $config['auth'] : null;

            if ($persistent) {
                $connected = $redis->pconnect($config['host'], $port, $timeout);

            } else {
                $connected = $redis->connect($config['host'], $port, $timeout);
            }

            if (!$connected) {
                throw new \RuntimeException("Could not connect to {$config['server']}");
            }

            if ($auth) {
                $redis->auth($auth);
            }

            $redis->select($index);
        }

        return $redis;
    }
}