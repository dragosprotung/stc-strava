<?php

declare(strict_types = 1);

namespace SportTrackerConnector\Strava\API;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJarInterface;

final class Authentication
{
    private const URL_LOGIN_GET = 'https://www.strava.com/login';

    private const URL_LOGIN_POST = 'https://www.strava.com/session';

    private const URL_AUTHENTICATE_TOKEN = 'https://www.strava.com/oauth/token';

    private const URL_ACCEPT_APPLICATION = 'https://www.strava.com/oauth/accept_application?client_id=%s&response_type=code&redirect_uri=http://localhost&scope=view_private,write';

    /**
     * @var string
     */
    protected $token;

    /**
     * @param string $token The token.
     */
    private function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the token.
     *
     * @return string
     */
    public function token(): string
    {
        return $this->token;
    }

    public static function fromToken(string $token): Authentication
    {
        return new static($token);
    }

    /**
     * @param ApiApplication $apiApplication
     * @param string $username
     * @param string $password
     * @param Client $client
     * @return Authentication
     */
    public static function fromLogin(
        ApiApplication $apiApplication,
        string $username,
        string $password,
        Client $client
    ): Authentication {
        $cookies = $client->getConfig('cookies');
        if (!$cookies instanceof CookieJarInterface) {
            throw new \InvalidArgumentException('The client expects to have a cookies enabled.');
        }

        // Perform the login to strava.com. This will set a cookie in the client.
        $response = $client->get(static::URL_LOGIN_GET);

        $domHTML = new \DOMDocument();
        libxml_use_internal_errors(true);
        $domHTML->loadHTML($response->getBody()->getContents());
        libxml_use_internal_errors(false);
        $xpath = new \DOMXPath($domHTML);
        $xml = $xpath->query('//input[@name="authenticity_token"]');
        $authenticityToken = trim($xml->item(0)->getAttribute('value'));
        if ($authenticityToken === '') {
            throw new \RuntimeException('Could not fetch the "authenticity_token" from the authorization page.');
        }

        $client->post(
            static::URL_LOGIN_POST,
            array(
                'form_params' => array(
                    'authenticity_token' => $authenticityToken,
                    'email' => $username,
                    'password' => $password
                )
            )
        );

        // Accept the application.
        $response = $client->post(
            sprintf(static::URL_ACCEPT_APPLICATION, $apiApplication->clientId()),
            array(
                'allow_redirects' => false, // disable redirects as we do not want Guzzle to redirect to localhost.
                'form_params' => array(
                    'authenticity_token' => $authenticityToken
                )
            )
        );

        $locationHeader = $response->getHeader('Location');
        if (count($locationHeader) === 0) {
            throw new \RuntimeException('Expected a redirect after application authorization.');
        }

        $redirectLocation = end($locationHeader);
        $urlQuery = parse_url($redirectLocation, PHP_URL_QUERY);
        parse_str($urlQuery, $urlQuery);

        return static::fromTokenExchange($apiApplication, $urlQuery['code'], $client);
    }

    /**
     * @param ApiApplication $apiApplication
     * @param string $authorizationCode
     * @param Client $client
     * @return Authentication
     */
    public static function fromTokenExchange(
        ApiApplication $apiApplication,
        string $authorizationCode,
        Client $client
    ): Authentication {
        $response = $client->post(
            static::URL_AUTHENTICATE_TOKEN,
            array(
                'form_params' => array(
                    'client_id' => $apiApplication->clientId(),
                    'client_secret' => $apiApplication->clientSecret(),
                    'code' => $authorizationCode
                )
            )
        );

        $jsonResponse = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);

        return new static($jsonResponse['access_token']);
    }

    public function __toString()
    {
        return $this->token();
    }
}
