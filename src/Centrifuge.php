<?php

namespace LaraComponents\Centrifuge;

use Exception;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use LaraComponents\Centrifuge\Contracts\Centrifuge as CentrifugeContract;
use Predis\Client as RedisClient;
use Predis\PredisException;

class Centrifuge implements CentrifugeContract
{
    const REDIS_SUFFIX = '.api';

    const API_PATH = '/api';

    /**
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * @var \Predis\Client
     */
    protected $redisClient;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $redisMethods = ['publish', 'broadcast', 'unsubscribe', 'disconnect'];

    /**
     * Create a new Centrifuge instance.
     *
     * @param array                 $config
     * @param \GuzzleHttp\Client    $httpClient
     * @param \Predis\Client|null   $redisClient
     */
    public function __construct(array $config, HttpClient $httpClient, RedisClient $redisClient = null)
    {
        $this->httpClient = $httpClient;
        $this->redisClient = $redisClient;

        $this->config = $this->initConfiguration($config);
    }

    /**
     * Init centrifuge configuration.
     *
     * @param  array  $config
     * @return array
     */
    protected function initConfiguration(array $config)
    {
        $defaults = [
            'url' => 'http://localhost:8000',
            'secret' => null,
            'redis_api' => false,
            'redis_prefix' => 'centrifugo',
            'redis_num_shards' => 0,
        ];

        foreach ($config as $key => $value) {
            if (array_key_exists($key, $defaults)) {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }

    /**
     * Send message into channel.
     *
     * @param string $channel
     * @param array $data
     * @param string $client
     * @return mixed
     */
    public function publish(string $channel, array $data, $client = null)
    {
        $params = ['channel' => $channel, 'data' => $data];

        if (! is_null($client)) {
            $params['client'] = $client;
        }

        return $this->send('publish', $params);
    }

    /**
     * Send message into multiple channel.
     *
     * @param array $channels
     * @param array $data
     * @param string $client
     * @return mixed
     */
    public function broadcast(array $channels, array $data, $client = null)
    {
        $params = ['channels' => $channels, 'data' => $data];

        if (! is_null($client)) {
            $params['client'] = $client;
        }

        return $this->send('broadcast', $params);
    }

    /**
     * Get channel presence information (all clients currently subscribed on this channel).
     *
     * @param string $channel
     * @return mixed
     */
    public function presense(string $channel)
    {
        return $this->send('presence', ['channel' => $channel]);
    }

    /**
     * Get channel history information (list of last messages sent into channel).
     *
     * @param string $channel
     * @return mixed
     */
    public function history(string $channel)
    {
        return $this->send('history', ['channel' => $channel]);
    }

    /**
     * Unsubscribe user from channel.
     *
     * @param string $user_id
     * @param string $channel
     * @return mixed
     */
    public function unsubscribe($user_id, $channel = null)
    {
        $params = ['user' => (string) $user_id];

        if (! is_null($channel)) {
            $params['channel'] = $channel;
        }

        return $this->send('unsubscribe', $params);
    }

    /**
     * Disconnect user by its ID.
     *
     * @param string $user_id
     * @return mixed
     */
    public function disconnect($user_id)
    {
        return $this->send('disconnect', ['user' => (string) $user_id]);
    }

    /**
     * Get channels information (list of currently active channels).
     *
     * @return mixed
     */
    public function channels()
    {
        return $this->send('channels');
    }

    /**
     * Get stats information about running server nodes.
     *
     * @return mixed
     */
    public function stats()
    {
        return $this->send('stats');
    }

    /**
     * Generate token.
     *
     * @param string $userOrClient
     * @param string $timestampOrChannel
     * @param string $info
     * @return string
     */
    public function generateToken($userOrClient, $timestampOrChannel, $info = '')
    {
        $ctx = hash_init('sha256', HASH_HMAC, $this->getSecret());
        hash_update($ctx, (string) $userOrClient);
        hash_update($ctx, (string) $timestampOrChannel);
        hash_update($ctx, (string) $info);

        return hash_final($ctx);
    }

    /**
     * Generate api sign.
     *
     * @param string $data
     * @return string
     */
    public function generateApiSign($data)
    {
        $ctx = hash_init('sha256', HASH_HMAC, $this->getSecret());
        hash_update($ctx, (string) $data);

        return hash_final($ctx);
    }

    /**
     * Get secret key.
     *
     * @return string
     */
    protected function getSecret()
    {
        return $this->config['secret'];
    }

    /**
     * Send message to centrifuge server.
     *
     * @param  string $method
     * @param  array  $params
     * @return mixed
     */
    protected function send(string $method, array $params = [])
    {
        try {
            if ($this->config['redis_api'] === true && ! is_null($this->redisClient) && in_array($method, $this->redisMethods)) {
                $result = $this->redisSend($method, $params);
            } else {
                $result = $this->httpSend($method, $params);
            }
        } catch (Exception $e) {
            $result = [
                'method' => $method,
                'error'  => $e,
                'body'   => $params,
            ];
        }

        return $result;
    }

    /**
     * Send message to centrifuge server from http client.
     *
     * @param  string $method
     * @param  array  $params
     * @return mixed
     */
    protected function httpSend(string $method, array $params = [])
    {
        $json = json_encode(['method' => $method, 'params' => $params]);

        $headers = [
            'Content-type' => 'application/json',
            'X-API-Sign' => $this->generateApiSign($json),
        ];

        try {
            $response = $this->httpClient->post($this->prepareUrl(), [
                'headers' => $headers,
                'body' => $json,
                'http_errors' => false,
            ]);

            $finally = json_decode((string) $response->getBody(), true)[0];
        } catch (ClientException $e) {
            throw $e;
        }

        return $finally;
    }

    /**
     * Prepare URL to send the http request.
     *
     * @return string
     */
    protected function prepareUrl()
    {
        $address = rtrim($this->config['url'], '/');

        if (substr_compare($address, static::API_PATH, -strlen(static::API_PATH)) !== 0) {
            $address .= static::API_PATH;
        }
        $address .= '/';

        return $address;
    }

    /**
     * Send message to centrifuge server from redis client.
     *
     * @param  string $method
     * @param  array  $params
     * @return mixed
     */
    protected function redisSend(string $method, array $params = [])
    {
        $json = json_encode(['method' => $method, 'params' => $params]);

        try {
            $result = $this->redisClient->rpush($this->getQueueKey(), $json);
        } catch (PredisException $e) {
            throw $e;
        }

        return [
            'method' => $method,
            'error'  => null,
            'body'   => null,
        ];
    }

    /**
     * Get queue key for redis engine.
     *
     * @return string
     */
    protected function getQueueKey()
    {
        $apiKey = $this->config['redis_prefix'].self::REDIS_SUFFIX;
        $numShards = (int) $this->config['redis_num_shards'];

        if ($numShards > 0) {
            return sprintf('%s.%d', $apiKey, rand(0, $numShards - 1));
        }

        return $apiKey;
    }
}
