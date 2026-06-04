<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! config('app.key')) {
            $base64Key = base64_encode(random_bytes(32));

            config(['app.key' => 'base64:'.$base64Key]);
            app()->forgetInstance('encrypter');
            app()->forgetInstance('cookie');
        }
    }
}
