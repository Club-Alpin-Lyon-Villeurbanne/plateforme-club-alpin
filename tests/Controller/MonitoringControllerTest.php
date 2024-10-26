<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCase;

class MonitoringControllerTest extends WebTestCase
{
    public function testItsWorkingAsExpected()
    {
        $this->client->request('GET', '/monitoring/200');
        $this->assertResponseStatusCodeSame(200);
        $this->client->request('GET', '/monitoring/500');
        $this->assertResponseStatusCodeSame(500);
        $this->client->request('GET', '/monitoring/404');
        $this->assertResponseStatusCodeSame(404);
    }
}
