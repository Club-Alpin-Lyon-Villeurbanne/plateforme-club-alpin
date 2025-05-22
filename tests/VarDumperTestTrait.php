<?php

namespace App\Tests;

use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\VarDumper\Cloner\VarCloner;

trait VarDumperTestTrait
{
    protected ?AbstractBrowser $client;

    public function assertDumpEquals($dump, $data, $message = '')
    {
        $this->assertSame(rtrim(\is_string($dump) ? $dump : $this->getVarDumperDump($dump)), $this->getVarDumperDump($data), $message);
    }

    public function assertResponseDumpEquals($dump, $message = '')
    {
        if (!$this->client) {
            throw new \Exception(sprintf('The "%s" method can be used in WebTestCase context', __METHOD__));
        }
        $this->assertDumpEquals($dump, json_decode($this->client->getResponse()->getContent(), true), $message);
    }

    public function assertDumpMatchesFormat($dump, $data, $message = '')
    {
        $this->assertStringMatchesFormat(rtrim($dump), $this->getVarDumperDump($data), $message);
    }

    public function assertResponseDumpMatchesFormat($dump, $message = '')
    {
        if (!$this->client) {
            throw new \Exception(sprintf('The "%s" method can be used in WebTestCase context', __METHOD__));
        }
        $this->assertDumpMatchesFormat($dump, json_decode($this->client->getResponse()->getContent(), true), $message);
    }

    private function getVarDumperDump($data)
    {
        $h = fopen('php://memory', 'r+b');
        $cloner = new VarCloner();
        $cloner->setMaxItems(-1);
        $dumper = new VarDumper($h);
        $dumper->setColors(false);
        $dumper->dump($cloner->cloneVar($data)->withRefHandles(false));
        $data = stream_get_contents($h, -1, 0);
        fclose($h);

        return rtrim($data);
    }
}
