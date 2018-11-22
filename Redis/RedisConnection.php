<?php declare(strict_types=1);

namespace SwagEssentials\Redis;

class RedisConnection
{
    /**
     * @var \Redis|\RedisCluster
     */
    private $connection;

    public function __construct($connection)
    {
        if (!$connection instanceof \Redis && !$connection instanceof \RedisCluster) {
            throw new \InvalidArgumentException('wrong connection type only Redis and RedisCluster ist supported!');
        }

        $this->connection = $connection;
    }

    public function close()
    {
        $this->connection->close();
    }

    public function get($key)
    {
        return $this->connection->get($key);
    }

    public function hSet($key, $hashKey, $value)
    {
        return $this->connection->hSet($key, $hashKey, $value);
    }

    public function psetex($key, $ttl, $value)
    {
        return $this->connection->psetex($key, $ttl, $value);
    }

    public function sScan($key, $iterator, $pattern = '', $count = 0)
    {
        return $this->connection->sScan($key, $iterator, $pattern, $count);
    }

    public function scan(&$iterator, $pattern = null, $count = 0)
    {
        return $this->connection->scan($iterator, $pattern, $count);
    }

    public function zScan($key, $iterator, $pattern = '', $count = 0)
    {
        return $this->connection->zScan($key, $iterator, $pattern, $count);
    }

    public function hScan($key, $iterator, $pattern = '', $count = 0)
    {
        return $this->connection->hScan($key, $iterator, $pattern, $count);
    }

    public function client($command, $arg = '')
    {
        return $this->connection->client($command, $arg);
    }

    public function slowlog($command)
    {
        return $this->connection->slowlog($command);
    }

    public function open($host, $port = 6379, $timeout = 0.0, $retry_interval = 0)
    {
        return $this->connection->open($host, $port, $timeout, $retry_interval);
    }

    public function pconnect($host, $port = 6379, $timeout = 0.0, $persistent_id = null)
    {
        return $this->connection->pconnect($host, $port, $timeout, $persistent_id);
    }

    public function popen($host, $port = 6379, $timeout = 0.0, $persistent_id = null)
    {
        return $this->connection->popen($host, $port, $timeout, $persistent_id);
    }

    public function setOption($name, $value)
    {
        return $this->connection->setOption($name, $value);
    }

    public function getOption($name)
    {
        return $this->connection->getOption($name);
    }

    public function ping()
    {
        return $this->connection->ping();
    }

    public function set($key, $value, $timeout = 0)
    {
        return $this->connection->set($key, $value, $timeout);
    }

    public function setex($key, $ttl, $value)
    {
        return $this->connection->setex($key, $ttl, $value);
    }

    public function setnx($key, $value)
    {
        return $this->connection->setnx($key, $value);
    }

    public function del($key1, $key2 = null, $key3 = null)
    {
        return $this->connection->del($key1, $key2, $key3);
    }

    public function delete($key1, $key2 = null, $key3 = null)
    {
        return $this->connection->delete($key1, $key2, $key3);
    }

    public function multi($mode = \Redis::MULTI)
    {
        return $this->connection->multi($mode);
    }

    public function exec()
    {
        return $this->connection->exec();
    }

    public function discard()
    {
        $this->connection->discard();
    }

    public function watch($key)
    {
        $this->connection->watch($key);
    }

    public function unwatch()
    {
        $this->connection->unwatch();
    }

    public function subscribe($channels, $callback)
    {
        return $this->connection->subscribe($channels, $callback);
    }

    public function psubscribe($patterns, $callback)
    {
        return $this->connection->psubscribe($patterns, $callback);
    }

    public function publish($channel, $message)
    {
        return $this->connection->publish($channel, $message);
    }

    public function pubsub($keyword, $argument)
    {
        return $this->connection->pubsub($keyword, $argument);
    }

    public function exists($key)
    {
        return $this->connection->exists($key);
    }

    public function incr($key)
    {
        return $this->connection->incr($key);
    }

    public function incrByFloat($key, $increment)
    {
        return $this->connection->incrByFloat($key, $increment);
    }

    public function incrBy($key, $value)
    {
        return $this->connection->incrBy($key, $value);
    }

    public function decr($key)
    {
        return $this->connection->decr($key);
    }

    public function decrBy($key, $value)
    {
        return $this->connection->decrBy($key, $value);
    }

    public function getMultiple(array $keys)
    {
        return $this->connection->getMultiple($keys);
    }

    public function lPush($key, $value1, $value2 = null, $valueN = null)
    {
        return $this->connection->lPush($key, $value1, $value2, $valueN);
    }

    public function rPush($key, $value1, $value2 = null, $valueN = null)
    {
        return $this->connection->rPush($key, $value1, $value2, $valueN);
    }

    public function lPushx($key, $value)
    {
        return $this->connection->lPushx($key, $value);
    }

    public function rPushx($key, $value)
    {
        return $this->connection->rPushx($key, $value);
    }

    public function lPop($key)
    {
        return $this->connection->lPop($key);
    }

    public function rPop($key)
    {
        return $this->connection->rPop($key);
    }

    public function blPop(array $keys, $timeout)
    {
        return $this->connection->blPop($keys, $timeout);
    }

    public function brPop(array $keys, $timeout)
    {
        return $this->connection->brPop($keys, $timeout);
    }

    public function lLen($key)
    {
        return $this->connection->lLen($key);
    }

    public function lSize($key)
    {
        return $this->connection->lSize($key);
    }

    public function lIndex($key, $index)
    {
        return $this->connection->lIndex($key, $index);
    }

    public function lGet($key, $index)
    {
        $this->connection->lGet($key, $index);
    }

    public function lSet($key, $index, $value)
    {
        return $this->connection->lSet($key, $index, $value);
    }

    public function lRange($key, $start, $end)
    {
        return $this->connection->lRange($key, $start, $end);
    }

    public function lGetRange($key, $start, $end)
    {
        return $this->connection->lGetRange($key, $start, $end);
    }

    public function lTrim($key, $start, $stop)
    {
        return $this->connection->lTrim($key, $start, $stop);
    }

    public function listTrim($key, $start, $stop)
    {
        return $this->connection->listTrim($key, $start, $stop);
    }

    public function lRem($key, $value, $count)
    {
        return $this->connection->lRem($key, $value, $count);
    }

    public function lRemove($key, $value, $count)
    {
        return $this->connection->lRemove($key, $value, $count);
    }

    public function lInsert($key, $position, $pivot, $value)
    {
        return $this->connection->lInsert($key, $position, $pivot, $value);
    }

    public function sAdd($key, $value1, $value2 = null, $valueN = null)
    {
        return $this->connection->sAdd($key, $value1, $value2, $valueN);
    }

    public function sAddArray($key, array $values)
    {
        return $this->connection->sAddArray($key, $values);
    }

    public function sRem($key, $member1, $member2 = null, $memberN = null)
    {
        return $this->connection->sRem($key, $member1, $member2, $memberN);
    }

    public function sRemove($key, $member1, $member2 = null, $memberN = null)
    {
        return $this->connection->sRemove($key, $member1, $member2, $memberN);
    }

    public function sMove($srcKey, $dstKey, $member)
    {
        return $this->connection->sMove($srcKey, $dstKey, $member);
    }

    public function sIsMember($key, $value)
    {
        return $this->connection->sIsMember($key, $value);
    }

    public function sContains($key, $value)
    {
        return $this->connection->sContains($key, $value);
    }

    public function sCard($key)
    {
        return $this->connection->sCard($key);
    }

    public function sPop($key)
    {
        return $this->connection->sPop($key);
    }

    public function sRandMember($key, $count = null)
    {
        return $this->connection->sRandMember($key, $count);
    }

    public function sInter($key1, $key2, $keyN = null)
    {
        return $this->connection->sInter($key1, $key2, $keyN);
    }

    public function sInterStore($dstKey, $key1, $key2, $keyN = null)
    {
        return $this->connection->sInterStore($dstKey, $key1, $key2, $keyN);
    }

    public function sUnion($key1, $key2, $keyN = null)
    {
        return $this->connection->sUnion($key1, $key2, $keyN);
    }

    public function sUnionStore($dstKey, $key1, $key2, $keyN = null)
    {
        return $this->connection->sUnionStore($dstKey, $key1, $key2, $keyN);
    }

    public function sDiff($key1, $key2, $keyN = null)
    {
        return $this->connection->sDiff($key1, $key2, $keyN);
    }

    public function sDiffStore($dstKey, $key1, $key2, $keyN = null)
    {
        return $this->connection->sDiffStore($dstKey, $key1, $key2, $keyN);
    }

    public function sMembers($key)
    {
        return $this->connection->sMembers($key);
    }

    public function sGetMembers($key)
    {
        return $this->connection->sGetMembers($key);
    }

    public function getSet($key, $value)
    {
        return $this->connection->getSet($key, $value);
    }

    public function randomKey()
    {
        return $this->connection->randomKey();
    }

    public function select($dbindex)
    {
        return $this->connection->select($dbindex);
    }

    public function move($key, $dbindex)
    {
        return $this->connection->move($key, $dbindex);
    }

    public function rename($srcKey, $dstKey)
    {
        return $this->connection->rename($srcKey, $dstKey);
    }

    public function renameKey($srcKey, $dstKey)
    {
        return $this->connection->renameKey($srcKey, $dstKey);
    }

    public function renameNx($srcKey, $dstKey)
    {
        return $this->connection->renameNx($srcKey, $dstKey);
    }

    public function expire($key, $ttl)
    {
        return $this->connection->expire($key, $ttl);
    }

    public function pExpire($key, $ttl)
    {
        return $this->connection->pExpire($key, $ttl);
    }

    public function setTimeout($key, $ttl)
    {
        return $this->connection->setTimeout($key, $ttl);
    }

    public function expireAt($key, $timestamp)
    {
        return $this->connection->expireAt($key, $timestamp);
    }

    public function pExpireAt($key, $timestamp)
    {
        return $this->connection->pExpireAt($key, $timestamp);
    }

    public function keys($pattern)
    {
        return $this->connection->keys($pattern);
    }

    public function getKeys($pattern)
    {
        return $this->connection->getKeys($pattern);
    }

    public function dbSize()
    {
        return $this->connection->dbSize();
    }

    public function auth($password)
    {
        return $this->connection->auth($password);
    }

    public function bgrewriteaof()
    {
        return $this->connection->bgrewriteaof();
    }

    public function slaveof($host = '127.0.0.1', $port = 6379)
    {
        return $this->connection->slaveof($host, $port);
    }

    public function object($string = '', $key = '')
    {
        return $this->connection->object($string, $key);
    }

    public function save()
    {
        return $this->connection->save();
    }

    public function bgsave()
    {
        return $this->connection->bgsave();
    }

    public function lastSave()
    {
        return $this->connection->lastSave();
    }

    public function wait($numSlaves, $timeout)
    {
        return $this->connection->wait($numSlaves, $timeout);
    }

    public function type($key)
    {
        return $this->connection->type($key);
    }

    public function append($key, $value)
    {
        return $this->connection->append($key, $value);
    }

    public function getRange($key, $start, $end)
    {
        return $this->connection->getRange($key, $start, $end);
    }

    public function substr($key, $start, $end)
    {
        return $this->connection->substr($key, $start, $end);
    }

    public function setRange($key, $offset, $value)
    {
        return $this->connection->setRange($key, $offset, $value);
    }

    public function strlen($key)
    {
        return $this->connection->strlen($key);
    }

    public function bitpos($key, $bit, $start = 0, $end = null)
    {
        return $this->connection->bitpos($key, $bit, $start, $end);
    }

    public function getBit($key, $offset)
    {
        return $this->connection->getBit($key, $offset);
    }

    public function setBit($key, $offset, $value)
    {
        return $this->connection->setBit($key, $offset, $value);
    }

    public function bitCount($key)
    {
        return $this->connection->bitCount($key);
    }

    public function bitOp($operation, $retKey, ...$keys)
    {
        return $this->connection->bitOp($operation, $retKey, $keys);
    }

    public function flushDB()
    {
        return $this->connection->flushDB();
    }

    public function flushAll()
    {
        return $this->connection->flushAll();
    }

    public function sort($key, $option = null)
    {
        return $this->connection->sort($key, $option);
    }

    public function info($option = null)
    {
        return $this->connection->info($option);
    }

    public function resetStat()
    {
        return $this->connection->resetStat();
    }

    public function ttl($key)
    {
        return $this->connection->ttl($key);
    }

    public function pttl($key)
    {
        return $this->connection->pttl($key);
    }

    public function persist($key)
    {
        return $this->connection->persist($key);
    }

    public function mset(array $array)
    {
        return $this->connection->mset($array);
    }

    public function mget(array $array)
    {
        return $this->connection->mget($array);
    }

    public function msetnx(array $array)
    {
        return $this->connection->msetnx($array);
    }

    public function rpoplpush($srcKey, $dstKey)
    {
        return $this->connection->rpoplpush($srcKey, $dstKey);
    }

    public function brpoplpush($srcKey, $dstKey, $timeout)
    {
        return $this->connection->brpoplpush($srcKey, $dstKey, $timeout);
    }

    public function zAdd($key, $score1, $value1, $score2 = null, $value2 = null, $scoreN = null, $valueN = null)
    {
        return $this->connection->zAdd($key, $score1, $value1, $score2, $value2, $scoreN, $valueN);
    }

    public function zRange($key, $start, $end, $withscores = null)
    {
        return $this->connection->zRange($key, $start, $end, $withscores);
    }

    public function zRem($key, $member1, $member2 = null, $memberN = null)
    {
        return $this->connection->zRem($key, $member1, $member2, $memberN);
    }

    public function zDelete($key, $member1, $member2 = null, $memberN = null)
    {
        return $this->connection->zDelete($key, $member1, $member2, $memberN);
    }

    public function zRevRange($key, $start, $end, $withscore = null)
    {
        return $this->connection->zRevRange($key, $start, $end, $withscore);
    }

    public function zRangeByScore($key, $start, $end, array $options = [])
    {
        return $this->connection->zRangeByScore($key, $start, $end, $options);
    }

    public function zRevRangeByScore($key, $start, $end, array $options = [])
    {
        return $this->connection->zRevRangeByScore($key, $start, $end, $options);
    }

    public function zRangeByLex($key, $min, $max, $offset = null, $limit = null)
    {
        return $this->connection->zRangeByLex($key, $min, $max, $offset, $limit);
    }

    public function zRevRangeByLex($key, $min, $max, $offset = null, $limit = null)
    {
        return $this->connection->zRevRangeByLex($key, $min, $max, $offset, $limit);
    }

    public function zCount($key, $start, $end)
    {
        return $this->connection->zCount($key, $start, $end);
    }

    public function zRemRangeByScore($key, $start, $end)
    {
        return $this->connection->zRemRangeByScore($key, $start, $end);
    }

    public function zDeleteRangeByScore($key, $start, $end)
    {
        return $this->connection->zDeleteRangeByScore($key, $start, $end);
    }

    public function zRemRangeByRank($key, $start, $end)
    {
        return $this->connection->zRemRangeByRank($key, $start, $end);
    }

    public function zDeleteRangeByRank($key, $start, $end)
    {
        return $this->connection->zDeleteRangeByRank($key, $start, $end);
    }

    public function zCard($key)
    {
        return $this->connection->zCard($key);
    }

    public function zSize($key)
    {
        return $this->connection->zSize($key);
    }

    public function zScore($key, $member)
    {
        return $this->connection->zScore($key, $member);
    }

    public function zRank($key, $member)
    {
        return $this->connection->zRank($key, $member);
    }

    public function zRevRank($key, $member)
    {
        return $this->connection->zRevRank($key, $member);
    }

    public function zIncrBy($key, $value, $member)
    {
        return $this->connection->zIncrBy($key, $value, $member);
    }

    public function zUnion($Output, $ZSetKeys, array $Weights = null, $aggregateFunction = 'SUM')
    {
        return $this->connection->zUnion($Output, $ZSetKeys, $Weights, $aggregateFunction);
    }

    public function zInter($Output, $ZSetKeys, array $Weights = null, $aggregateFunction = 'SUM')
    {
        return $this->connection->zInter($Output, $ZSetKeys, $Weights, $aggregateFunction);
    }

    public function hSetNx($key, $hashKey, $value)
    {
        return $this->connection->hSetNx($key, $hashKey, $value);
    }

    public function hGet($key, $hashKey)
    {
        return $this->connection->hGet($key, $hashKey);
    }

    public function hLen($key)
    {
        return $this->connection->hLen($key);
    }

    public function hDel($key, $hashKey1, $hashKey2 = null, $hashKeyN = null)
    {
        return $this->connection->hDel($key, $hashKey1, $hashKey2, $hashKeyN);
    }

    public function hKeys($key)
    {
        return $this->connection->hKeys($key);
    }

    public function hVals($key)
    {
        return $this->connection->hVals($key);
    }

    public function hGetAll($key)
    {
        return $this->connection->hGetAll($key);
    }

    public function hExists($key, $hashKey)
    {
        return $this->connection->hExists($key, $hashKey);
    }

    public function hIncrBy($key, $hashKey, $value)
    {
        return $this->connection->hIncrBy($key, $hashKey, $value);
    }

    public function hIncrByFloat($key, $field, $increment)
    {
        return $this->connection->hIncrByFloat($key, $field, $increment);
    }

    public function hMSet($key, $hashKeys)
    {
        return $this->connection->hMSet($key, $hashKeys);
    }

    public function hMGet($key, $hashKeys)
    {
        return $this->connection->hMGet($key, $hashKeys);
    }

    public function config($operation, $key, $value)
    {
        return $this->connection->config($operation, $key, $value);
    }

    public function evaluate($script, $args = [], $numKeys = 0)
    {
        return $this->connection->evaluate($script, $args, $numKeys);
    }

    public function evalSha($scriptSha, $args = [], $numKeys = 0)
    {
        return $this->connection->evalSha($scriptSha, $args, $numKeys);
    }

    public function evaluateSha($scriptSha, $args = [], $numKeys = 0)
    {
        return $this->connection->evaluateSha($scriptSha, $args, $numKeys);
    }

    public function script($command, $script)
    {
        return $this->connection->script($command, $script);
    }

    public function getLastError()
    {
        return $this->connection->getLastError();
    }

    public function clearLastError()
    {
        return $this->connection->clearLastError();
    }

    public function _prefix($value)
    {
        return $this->connection->_prefix($value);
    }

    public function _unserialize($value)
    {
        return $this->connection->_unserialize($value);
    }

    public function _serialize($value)
    {
        return $this->connection->_serialize($value);
    }

    public function dump($key)
    {
        return $this->connection->dump($key);
    }

    public function restore($key, $ttl, $value)
    {
        return $this->connection->restore($key, $ttl, $value);
    }

    public function migrate($host, $port, $key, $db, $timeout, $copy = false, $replace = false)
    {
        return $this->connection->migrate($host, $port, $key, $db, $timeout, $copy, $replace);
    }

    public function time()
    {
        return $this->connection->time();
    }

    public function pfAdd($key, array $elements)
    {
        return $this->connection->pfAdd($key, $elements);
    }

    public function pfCount($key)
    {
        return $this->connection->pfCount($key);
    }

    public function pfMerge($destkey, array $sourcekeys)
    {
        return $this->connection->pfMerge($destkey, $sourcekeys);
    }

    public function rawCommand($command, $arguments)
    {
        return $this->connection->rawCommand($command, $arguments);
    }

    public function getMode()
    {
        return $this->connection->getMode();
    }
}
