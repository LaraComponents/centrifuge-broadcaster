<?php

namespace LaraComponents\Centrifuge\Contracts;

interface Centrifuge
{
    /**
     * Send message into channel.
     *
     * @param string $channel
     * @param array $data
     * @param string $client
     * @return mixed
     */
    public function publish($channel, array $data, $client = null);

    /**
     * Send message into multiple channel.
     *
     * @param array $channels
     * @param array $data
     * @param string $client
     * @return mixed
     */
    public function broadcast(array $channels, array $data, $client = null);

    /**
     * Get channel presence information (all clients currently subscribed on this channel).
     *
     * @param string $channel
     * @return mixed
     */
    public function presence($channel);

    /**
     * Get channel history information (list of last messages sent into channel).
     *
     * @param string $channel
     * @return mixed
     */
    public function history($channel);

    /**
     * Unsubscribe user from channel.
     *
     * @param string $user_id
     * @param string $channel
     * @return mixed
     */
    public function unsubscribe($user_id, $channel = null);

    /**
     * Disconnect user by its ID.
     *
     * @param string $user_id
     * @return mixed
     */
    public function disconnect($user_id);

    /**
     * Get channels information (list of currently active channels).
     *
     * @return mixed
     */
    public function channels();

    /**
     * Get stats information about running server nodes.
     *
     * @return mixed
     */
    public function stats();

    /**
     * Generate token.
     *
     * @param string $userOrClient
     * @param string $timestampOrChannel
     * @param string $info
     * @return string
     */
    public function generateToken($userOrClient, $timestampOrChannel, $info = '');

    /**
     * Generate api sign.
     *
     * @param string $data
     * @return string
     */
    public function generateApiSign($data);
}
