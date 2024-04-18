<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;

class MonitoringControllerTest extends WebTestCase
{
    public function testItsWorkingAsExpected()
    {
        static::$client->request('GET', '/monitoring/200');
        $this->assertResponseStatusCodeSame(200);
        static::$client->request('GET', '/monitoring/500');
        $this->assertResponseStatusCodeSame(500);
        static::$client->request('GET', '/monitoring/404');
        $this->assertResponseStatusCodeSame(404);
    }
}
