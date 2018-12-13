<?php

namespace App\User;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use SilverStripe\Core\Injector\Factory;

class ClientFactory implements Factory
{
    const maxRetries = 5;

    public function create($service, array $params = [])
    {
        $handlerStack = HandlerStack::create(new CurlHandler());
        // setup a retry functionality that will retry the request self::maxRetries for connection errors and 500 status code
        // errors. This will by default have an exponential back-off (sleep) between retries calculated as pow(2, $retries - 1);
        $handlerStack->push(Middleware::retry(
            function ($retries, $request, $response, $exception) {
                if ($retries > self::maxRetries) {
                    return false;
                }
                if ($exception instanceof ConnectException) {
                    return true;
                }
                if ($response && $response->getStatusCode() >= 500) {
                    return true;
                }

                return false;
            },
            function ($retries) {
                // back-off, aka sleep between retries in milliseconds
                return (int) 2 ** ($retries - 1) * 10;
            }
        ));
        $client = new Client([
            'handler' => $handlerStack,
            'timeout' => 5.0,
            'connect_timeout' => 5.0,
        ]);

        return $client;
    }
}
