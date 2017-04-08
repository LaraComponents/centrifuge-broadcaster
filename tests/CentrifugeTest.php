<?php

namespace LaraComponents\Centrifuge\Test;

class CentrifugeTest extends TestCase
{
    public function testGenerateToken()
    {
        $timestamp = 1491650279;
        $user_id = 1;
        $info = json_encode([
            'first_name' => 'Nikita',
            'last_name' => 'Stenin',
        ]);
        $client = '0c951315-be0e-4516-b99e-05e60b0cc307';
        $channel = 'test-channel';

        $clientToken1 = $this->centrifuge->generateToken($user_id, $timestamp);
        $this->assertEquals('558880399d21bd215e4d1558dd95efad9b82f829a1d44910fb611eeeffac3c50', $clientToken1);

        $clientToken2 = $this->centrifuge->generateToken($user_id, $timestamp, $info);
        $this->assertEquals('37722e22cee00160d777fd8d594dd831a9d5404016d5515a2cffc7c102ea67ee', $clientToken2);

        $channelSign = $this->centrifuge->generateToken($client, $channel);
        $this->assertEquals('02fc39b64c252108e80cfdcab2ef774f13a181f5149d21cebabd6eca08d231d2', $channelSign);
    }

    public function testGenerateApiSign()
    {
        $json = json_encode(['method' => 'publish', 'params' => [
            'channel' => 'test-channel',
        ]]);

        $apiSign = $this->centrifuge->generateApiSign($json);
        $this->assertEquals('11950468714031c2e0b95e284482ba6e8d9e17e1a6115eb0db6feded7581ebba', $apiSign);
    }

    public function testCentrifugeApi()
    {
        $publish = $this->centrifuge->publish('test-channel', ['event' => 'test-event']);
        $this->assertEquals($publish, [
            'method' => 'publish',
            'error' => null,
            'body' => null,
        ]);

        $broadcast = $this->centrifuge->broadcast(['test-channel-1', 'test-channel-2'], ['event' => 'test-event']);
        $this->assertEquals($broadcast, [
            'method' => 'broadcast',
            'error' => null,
            'body' => null,
        ]);

        $presence = $this->centrifuge->presence('test-channel');
        $this->assertEquals($presence, [
            'method' => 'presence',
            'error' => null,
            'body' => [
                'channel' => 'test-channel',
                'data' => [],
            ],
        ]);

        $history = $this->centrifuge->history('test-channel');
        $this->assertEquals($history['method'], 'history');
        $this->assertEquals($history['error'], 'not available');
        $this->assertEquals($history['body']['channel'], 'test-channel');

        $channels = $this->centrifuge->channels();
        $this->assertEquals($channels, [
            'method' => 'channels',
            'error' => null,
            'body' => [
                'data' => [],
            ],
        ]);

        $unsubscribe = $this->centrifuge->unsubscribe(1);
        $this->assertEquals($unsubscribe, [
            'method' => 'unsubscribe',
            'error' => null,
            'body' => null,
        ]);

        $disconnect = $this->centrifuge->disconnect(1);
        $this->assertEquals($disconnect, [
            'method' => 'disconnect',
            'error' => null,
            'body' => null,
        ]);

        $stats = $this->centrifuge->stats();
        $this->assertEquals($stats['method'], 'stats');
        $this->assertEquals($stats['error'], null);
    }
}