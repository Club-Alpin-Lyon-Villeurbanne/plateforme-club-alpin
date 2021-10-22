<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MonitoringControllerTest extends WebTestCase
{
    public function testItsWorkingAsExpected()
    {
        $client = static::createClient();

        $client->request('GET', '/monitoring/200');
        $this->assertResponseStatusCodeSame(200);
        $client->request('GET', '/monitoring/500');
        $this->assertResponseStatusCodeSame(500);
        $client->request('GET', '/monitoring/404');
        $this->assertResponseStatusCodeSame(404);
    }
}
