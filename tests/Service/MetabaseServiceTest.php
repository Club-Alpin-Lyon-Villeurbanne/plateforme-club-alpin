<?php

namespace App\Tests\Service;

use App\Service\MetabaseService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PHPUnit\Framework\TestCase;

class MetabaseServiceTest extends TestCase
{
    private string $secretKey = 'test-secret-key-12345';
    private MetabaseService $service;

    protected function setUp(): void
    {
        $this->service = new MetabaseService($this->secretKey);
    }

    public function testGenerateDashboardUrlBasic(): void
    {
        $dashboardId = 123;
        $url = $this->service->generateDashboardUrl($dashboardId);

        $this->assertStringStartsWith('https://wltukblxoyxlobfldmpp-metabase.services.clever-cloud.com/embed/dashboard/', $url);
        $this->assertStringContainsString('#bordered=true&titled=true', $url);
    }

    public function testGenerateDashboardUrlContainsToken(): void
    {
        $dashboardId = 456;
        $url = $this->service->generateDashboardUrl($dashboardId);

        // Extract token from URL
        preg_match('/\/embed\/dashboard\/([^#]+)/', $url, $matches);
        $this->assertNotEmpty($matches[1]);

        $token = $matches[1];
        $this->assertNotEmpty($token);
    }

    public function testGenerateDashboardUrlWithParams(): void
    {
        $dashboardId = 789;
        $params = ['var1' => 'value1', 'var2' => 'value2'];
        $url = $this->service->generateDashboardUrl($dashboardId, $params);

        $this->assertStringStartsWith('https://wltukblxoyxlobfldmpp-metabase.services.clever-cloud.com/embed/dashboard/', $url);
    }

    public function testGenerateDashboardUrlWithEmptyParams(): void
    {
        $dashboardId = 111;
        $url = $this->service->generateDashboardUrl($dashboardId, []);

        $this->assertStringStartsWith('https://wltukblxoyxlobfldmpp-metabase.services.clever-cloud.com/embed/dashboard/', $url);
        $this->assertStringContainsString('#bordered=true&titled=true', $url);
    }

    public function testGenerateDashboardUrlTokenContainsDashboardId(): void
    {
        $dashboardId = 999;
        $url = $this->service->generateDashboardUrl($dashboardId);

        // Extract token
        preg_match('/\/embed\/dashboard\/([^#]+)/', $url, $matches);
        $token = $matches[1];

        // Decode JWT without verification (since it's for testing)
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            $this->assertEquals($dashboardId, $decoded->resource->dashboard);
        } catch (\Exception $e) {
            $this->fail('Failed to decode JWT: ' . $e->getMessage());
        }
    }

    public function testGenerateDashboardUrlTokenContainsParams(): void
    {
        $dashboardId = 555;
        $params = ['filter1' => 'test', 'filter2' => 'another'];
        $url = $this->service->generateDashboardUrl($dashboardId, $params);

        // Extract token
        preg_match('/\/embed\/dashboard\/([^#]+)/', $url, $matches);
        $token = $matches[1];

        // Decode JWT
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            $this->assertEquals($params, (array) $decoded->params);
        } catch (\Exception $e) {
            $this->fail('Failed to decode JWT: ' . $e->getMessage());
        }
    }

    public function testGenerateDashboardUrlTokenHasExpiration(): void
    {
        $dashboardId = 222;
        $url = $this->service->generateDashboardUrl($dashboardId);

        // Extract token
        preg_match('/\/embed\/dashboard\/([^#]+)/', $url, $matches);
        $token = $matches[1];

        // Decode JWT
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            $this->assertNotNull($decoded->exp);
            // Token should expire in about 10 minutes (600 seconds)
            $now = time();
            $expiresIn = $decoded->exp - $now;
            $this->assertGreaterThan(590, $expiresIn);
            $this->assertLessThan(610, $expiresIn);
        } catch (\Exception $e) {
            $this->fail('Failed to decode JWT: ' . $e->getMessage());
        }
    }

    public function testGenerateDashboardUrlWithDifferentDashboardIds(): void
    {
        $dashboardIds = [1, 100, 999, 5000];

        foreach ($dashboardIds as $id) {
            $url = $this->service->generateDashboardUrl($id);
            $this->assertStringStartsWith('https://wltukblxoyxlobfldmpp-metabase.services.clever-cloud.com/embed/dashboard/', $url);
        }
    }

    public function testGenerateDashboardUrlUrlFormat(): void
    {
        $dashboardId = 333;
        $url = $this->service->generateDashboardUrl($dashboardId);

        // URL should match expected format
        // JWT tokens contain base64url characters: a-z A-Z 0-9 - _ + .
        $pattern = '/^https:\/\/wltukblxoyxlobfldmpp-metabase\.services\.clever-cloud\.com\/embed\/dashboard\/[a-zA-Z0-9_\-\+\.]+#bordered=true&titled=true$/';
        $this->assertMatchesRegularExpression($pattern, $url);
    }

    public function testGenerateDashboardUrlContainsBorderedTrue(): void
    {
        $dashboardId = 444;
        $url = $this->service->generateDashboardUrl($dashboardId);

        $this->assertStringContainsString('bordered=true', $url);
    }

    public function testGenerateDashboardUrlContainsTitledTrue(): void
    {
        $dashboardId = 555;
        $url = $this->service->generateDashboardUrl($dashboardId);

        $this->assertStringContainsString('titled=true', $url);
    }

    public function testGenerateDashboardUrlWithComplexParams(): void
    {
        $dashboardId = 666;
        $params = [
            'userId' => 123,
            'commissionCode' => 'ALPI',
            'dateFrom' => '2024-01-01',
            'dateTo' => '2024-12-31',
        ];
        $url = $this->service->generateDashboardUrl($dashboardId, $params);

        // Extract token and verify params are encoded
        preg_match('/\/embed\/dashboard\/([^#]+)/', $url, $matches);
        $token = $matches[1];

        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            $decodedParams = (array) $decoded->params;
            $this->assertEquals($params, $decodedParams);
        } catch (\Exception $e) {
            $this->fail('Failed to decode JWT: ' . $e->getMessage());
        }
    }

    public function testGenerateDashboardUrlConsistency(): void
    {
        $dashboardId = 777;
        $params = ['key' => 'value'];

        // Generate URL twice with same params
        $url1 = $this->service->generateDashboardUrl($dashboardId, $params);
        $url2 = $this->service->generateDashboardUrl($dashboardId, $params);

        // URLs should have same base but different tokens (due to expiration time)
        $this->assertStringStartsWith(
            'https://wltukblxoyxlobfldmpp-metabase.services.clever-cloud.com/embed/dashboard/',
            $url1
        );
        $this->assertStringStartsWith(
            'https://wltukblxoyxlobfldmpp-metabase.services.clever-cloud.com/embed/dashboard/',
            $url2
        );
    }

    public function testGenerateDashboardUrlWithZeroDashboardId(): void
    {
        $dashboardId = 0;
        $url = $this->service->generateDashboardUrl($dashboardId);

        preg_match('/\/embed\/dashboard\/([^#]+)/', $url, $matches);
        $token = $matches[1];

        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            $this->assertEquals(0, $decoded->resource->dashboard);
        } catch (\Exception $e) {
            $this->fail('Failed to decode JWT: ' . $e->getMessage());
        }
    }

    public function testGenerateDashboardUrlWithNegativeDashboardId(): void
    {
        $dashboardId = -1;
        $url = $this->service->generateDashboardUrl($dashboardId);

        preg_match('/\/embed\/dashboard\/([^#]+)/', $url, $matches);
        $token = $matches[1];

        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            $this->assertEquals(-1, $decoded->resource->dashboard);
        } catch (\Exception $e) {
            $this->fail('Failed to decode JWT: ' . $e->getMessage());
        }
    }

    public function testGenerateDashboardUrlWithSpecialCharsInParams(): void
    {
        $dashboardId = 888;
        $params = [
            'search' => 'test@example.com',
            'title' => 'L\'event de 2024',
        ];
        $url = $this->service->generateDashboardUrl($dashboardId, $params);

        preg_match('/\/embed\/dashboard\/([^#]+)/', $url, $matches);
        $token = $matches[1];

        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            $decodedParams = (array) $decoded->params;
            $this->assertEquals($params['search'], $decodedParams['search']);
            $this->assertEquals($params['title'], $decodedParams['title']);
        } catch (\Exception $e) {
            $this->fail('Failed to decode JWT: ' . $e->getMessage());
        }
    }
}
