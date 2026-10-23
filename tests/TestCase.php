<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null): TestResponse
    {
        $auth = $this->app['auth'];
        $defaultGuard = $auth->getDefaultDriver();
        $response = parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
        $webUser = $auth->guard('web')->user();
        $auth->forgetGuards();
        if ($webUser) {
            $auth->guard('web')->setUser($webUser);
        }
        $auth->shouldUse($defaultGuard);

        return $response;
    }
}
