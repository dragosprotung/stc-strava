<?php

declare(strict_types = 1);

namespace SportTrackerConnector\Strava\Test\API\Authentication;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use SportTrackerConnector\Strava\API\ApiApplication;
use SportTrackerConnector\Strava\API\Authentication;

/**
 * Test case for Authentication.
 */
class AuthenticationTest extends \PHPUnit_Framework_TestCase
{

    public function testAuthenticationFromToken()
    {
        $token = 'my_token';
        $authentication = Authentication::fromToken($token);

        self::assertSame($token, $authentication->token());
        self::assertSame($token, (string)$authentication);
    }

    /**
     * @group ttt
     */
    public function testFromLogin()
    {
        $username = 'username';
        $password = 'password';
        $apiApplication = new ApiApplication('client_id', 'client_secret');

        $mockHandler = new MockHandler(
            [
                new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/' . $this->getName() . '-login.html')),
                new Response(200),
                new Response(200, ['Location' => ['localhost?code=token_exchange_code']]),
                new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/' . $this->getName() . '-te.json'))
            ]
        );

        $handler = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handler, 'cookies' => true]);
        $authentication = Authentication::fromLogin($apiApplication, $username, $password, $client);

        $token = '83ebeabdec09f6670863766f792ead24d61fe3f9';
        self::assertSame($token, $authentication->token());
        self::assertSame($token, (string)$authentication);
    }

    public function testAuthenticationFromTokenExchange()
    {
        $apiApplication = new ApiApplication('client_id', 'client_secret');
        $authorizationCode = 'authorization_code';

        $mockHandler = new MockHandler(
            [
                new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/' . $this->getName() . '.json'))
            ]
        );

        $handler = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handler]);
        $authentication = Authentication::fromTokenExchange(
            $apiApplication,
            $authorizationCode,
            $client
        );

        $token = '83ebeabdec09f6670863766f792ead24d61fe3f9';
        self::assertSame($token, $authentication->token());
        self::assertSame($token, (string)$authentication);
    }
}
