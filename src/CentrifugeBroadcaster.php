<?php

namespace LaraComponents\Centrifuge;

use Exception;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use LaraComponents\Centrifuge\Contracts\Centrifuge;
use Symfony\Component\HttpKernel\Exception\HttpException;
use LaraComponents\Centrifuge\Contracts\Centrifuge as CentrifugeContract;

class CentrifugeBroadcaster extends Broadcaster
{
    /**
     * The Centrifuge SDK instance.
     *
     * @var \LaraComponents\Centrifuge\Contracts\Centrifuge
     */
    protected $centrifuge;

    /**
     * Create a new broadcaster instance.
     *
     * @param  \LaraComponents\Centrifuge\Contracts\Centrifuge  $centrifuge
     */
    public function __construct(CentrifugeContract $centrifuge)
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
        if ($request->user()) {
            $client = $request->get('client', '');
            $channels = $request->get('channels', []);
            $channels = is_array($channels) ? $channels : [$channels];

            $response = [];
            $info = json_encode([]);
            foreach ($channels as $channel) {
                $channelName = (substr($channel, 0, 1) === '$') ? substr($channel, 1) : $channel;

                try {
                    $result = $this->verifyUserCanAccessChannel($request, $channelName);
                } catch (HttpException $e) {
                    $result = false;
                }

                $response[$channel] = $result ? [
                    'sign' => $this->centrifuge->generateToken($client, $channel, $info),
                    'info' => $info,
                ] : [
                    'status' => 403,
                ];
            }

            return response()->json($response);
        } else {
            throw new HttpException(401);
        }
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
        return $result;
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

        $socket = null;
        if (array_key_exists('socket', $payload)) {
            $socket = $payload['socket'];
            unset($payload['socket']);
        }

        $response = $this->centrifuge->broadcast($this->formatChannels($channels), $payload, $socket);

        if (is_array($response) && is_null($response['error'])) {
            return;
        }

        throw new BroadcastException(
            $response['error'] instanceof Exception ? $response['error']->getMessage() : $response['error']
        );
    }

    /**
     * Get the Centrifuge instance.
     *
     * @return \LaraComponents\Centrifuge\Contracts\Centrifuge
     */
    public function getCentrifuge()
    {
        return $this->centrifuge;
    }
}
