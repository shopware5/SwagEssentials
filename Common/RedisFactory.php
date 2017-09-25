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
                /**
                 * Persistent connections are unique by host + port + timeout OR host + persistent_id OR unix_socket + persistent_id
                 * The selected database is NOT part of this unique key. Therefore, multiple redis instances with different
                 * databases might actually share the same connection and therefore e.g. write / flush the wrong database.
                 *
                 * Therefore, were are setting the 4th param "persistent_id" to the select database, even though
                 * the IDE  might indicate, that there is no 4th param. Refer to https://github.com/phpredis/phpredis#pconnect-popen
                 * for more information.
                 */
                $connected = $redis->pconnect($config['host'], $port, $timeout, $index);

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
