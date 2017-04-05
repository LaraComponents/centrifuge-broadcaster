<?php

namespace LaraComponents\Centrifuge;

use Exception;
use Illuminate\Broadcasting\BroadcastException;
use LaraComponents\Centrifuge\Contracts\Centrifuge;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        if ($request->user()) {
            $client = $request->get('client', '');
            $channels = $request->get('channels', []);
            $channels = is_array($channels) ? $channels : [$channels];

            $response = [];
            $info = json_encode([]);
            foreach ($channels as $channel) {
                try {
                    $result = parent::verifyUserCanAccessChannel($request, $channel);
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

        try {
            $response = $this->centrifuge->broadcast($this->formatChannels($channels), $payload);
        } catch (Exception $e) {
            throw new BroadcastException($e->getMessage());
        }
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
