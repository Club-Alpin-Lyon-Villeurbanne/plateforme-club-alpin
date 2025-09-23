<?php

namespace App\Tests\Controller;

use App\Entity\UserAttr;
use App\Tests\WebTestCase;

class EventManagementControllerTest extends WebTestCase
{
    public function testManageEventsForbiddenWhenNoRights(): void
    {
        $this->client->request('GET', '/gestion-des-sorties.html');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testManageEventsAllowedForCommissionResponsable(): void
    {
        $user = $this->signup();
        $this->signin($user);

        $event = $this->createEvent($user);
        $this->addAttribute($user, UserAttr::RESPONSABLE_COMMISSION, 'commission:' . $event->getCommission()->getCode());

        $this->client->request('GET', '/gestion-des-sorties.html');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testLegalManageEventsForbiddenWhenNoRights(): void
    {
        $this->client->request('GET', '/validation-des-sorties.html');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testLegalManageEventsAllowedForPresident(): void
    {
        $user = $this->signup();
        $this->signin($user);
        $this->addAttribute($user, UserAttr::PRESIDENT);

        $this->client->request('GET', '/validation-des-sorties.html');
        $this->assertResponseStatusCodeSame(200);
    }
}
