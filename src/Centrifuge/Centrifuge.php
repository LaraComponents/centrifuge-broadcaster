<?php

namespace LaraComponents\Centrifuge;

use Exception;
use GuzzleHttp\Client;
use LaraComponents\Centrifuge\Exceptions\RequestException;

class Centrifuge
{
    /**
     * @var string
     */
    protected $endpoint;

    /**
     * @var string
     */
    protected $secret;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * Centrifugo constructor.
     *
     * @param $endpoint
     * @param $secret
     */
    public function __construct(string $endpoint, string $secret)
    {
        $this->endpoint = $endpoint;
        $this->secret = $secret;
        $this->client = new Client();
    }

    /**
     * Send message into channel.
     *
     * @param $channel
     * @param array $data
     * @return mixed
     */
    public function publish($channel, array $data, $client = null)
    {
        $params = ['channel' => $channel, 'data' => $data];

        if(! is_null($client)) {
            $params['client'] = $client;
        }

        return $this->send('publish', $params);
    }

    /**
     * Send message into multiple channel.
     *
     * @param array $channels
     * @param array $data
     * @return mixed
     */
    public function broadcast(array $channels, array $data, $client = null)
    {
        $params = ['channels' => $channels, 'data' => $data];

        if(! is_null($client)) {
            $params['client'] = $client;
        }

        return $this->send('broadcast', $params);
    }

    /**
     * Get channel presence information (all clients currently subscribed on this channel).
     *
     * @param $channel
     * @return mixed
     */
    public function presense($channel)
    {
        return $this->send('presence', ['channel' => $channel]);
    }

    /**
     * Get channel history information (list of last messages sent into channel).
     *
     * @param $channel
     * @return mixed
     */
    public function history($channel)
    {
        return $this->send('history', ['channel' => $channel]);
    }

    /**
     * Unsubscribe user from channel.
     *
     * @param $channel
     * @param $user_id
     * @return mixed
     */
    public function unsubscribe($user_id, $channel = null)
    {
        $params = ['user' => (string) $user_id];

        if(! is_null($channel)) {
            $params['channel'] = $channel;
        }

        return $this->send('unsubscribe', $params);
    }

    /**
     * Disconnect user by its ID
     *
     * @param $userId
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
     * Generate client connection token
     *
     * @param string $user
     * @param string $timestamp
     * @param string $info
     * @return string
     */
    public function generateToken($user, $timestamp, $info = "")
    {
        $ctx = hash_init("sha256", HASH_HMAC, $this->secret);
        hash_update($ctx, $user);
        hash_update($ctx, $timestamp);
        hash_update($ctx, $info);

        return hash_final($ctx);
    }

    /**
     * Generates connection settings for centrifuge client
     *
     * @param bool $isSockJS
     * @param array $options
     * @return array
     */
    public function getConnection($user, $isSockJS = false, $options = [])
    {
        $timestamp = (string) time();
        $user = $user ? (string) $user  : '';

        $info = array_key_exists('info', $options) ? $options['info'] : '';
        return array_merge(
            $options,
            [
                'url'       => rtrim($this->endpoint, '/') . ($isSockJS ? '/connection' : ''),
                'user'      => $user,
                'timestamp' => $timestamp,
                'token'     => $this->generateToken($info, $timestamp, $info),
            ]
        );
    }

    public function generateApiSign(string $data)
    {
        $ctx = hash_init("sha256", HASH_HMAC, $this->secret);
        hash_update($ctx, $data);
        return hash_final($ctx);
    }

    public function generateChannelSign($client, $channel, $info = '')
    {
        $ctx = hash_init('sha256', HASH_HMAC, $this->secret);
        hash_update($ctx, (string) $client);
        hash_update($ctx, (string) $channel);
        hash_update($ctx, (string) $info);

        return hash_final($ctx);
    }

    protected function send($method, $params = [])
    {
        $data = json_encode(["method" => $method, "params" => $params]);
        $sign = $this->generateApiSign($data);
        $headers = [ 'Content-type' => 'application/json', 'X-API-Sign' => $sign ];

        try {
            $rawResponse = $this->client->post($this->prepareUrl(), [
                'headers' => $headers,
                'body' => $data,
                'http_errors' => false
            ]);
        }
        catch (Exception $e) {
            throw new RequestException($e->getMessage());
        }

        if($rawResponse->getStatusCode() !== 200) {
            throw new RequestException(sprintf("Wrong status code: %d", $rawResponse->getStatusCode()));
        }

        $response = json_decode($rawResponse->getBody())[0];

        if(isset($response->error) && $response->error) {
            throw new ResponseException($response->error);
        }

        return $response->body;
    }

    protected function prepareUrl()
    {
        $address = rtrim($this->endpoint, "/");
        $api_path = "/api";

        if(substr_compare($address, $api_path, -strlen($api_path)) !== 0) {
            $address .= $api_path;
        }
        $address .= "/";

        return $address;
    }
}
