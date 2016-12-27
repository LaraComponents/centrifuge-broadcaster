<?php

namespace LaraComponents\Centrifuge;

use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CentrifugeBroadcaster extends Broadcaster
{
    /**
     * The Centrifuge SDK instance.
     *
     * @var \LaraComponents\Centrifuge\Centrifuge
     */
    protected $centrifuge;

    /**
     * Create a new broadcaster instance.
     *
     * @param  \LaraComponents\Centrifuge\Centrifuge  $pusher
     * @return void
     */
    public function __construct(Centrifuge $centrifuge)
    {
        $this->centrifuge = $centrifuge;
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function auth($request)
    {
        return true;
    }

    /**
     * Return the valid authentication response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $result
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {

    }

    /**
     * Broadcast the given event.
     *
     * @param  array  $channels
     * @param  string  $event
     * @param  array  $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $payload['event'] = $event;

        try {
            $response = $this->centrifuge->broadcast($this->formatChannels($channels), $payload);
        } catch (Exception $e) {
            throw new BroadcastException($e->getMessage());
        }
    }

    /**
     * Get the CentrifugeManager instance.
     *
     * @return \LaraComponents\Centrifuge\Centrifuge
     */
    public function getCentrifuge()
    {
        return $this->centrifuge;
    }
}
