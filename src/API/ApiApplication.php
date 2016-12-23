<?php

declare(strict_types = 1);

namespace SportTrackerConnector\Strava\API;

/**
 * API application value object.
 */
final class ApiApplication
{
    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientSecret;

    /**
     * @param string $clientId
     * @param string $clientSecret
     */
    public function __construct(string $clientId, string $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public function clientId() : string
    {
        return $this->clientId;
    }

    public function clientSecret() : string
    {
        return $this->clientSecret;
    }
}
