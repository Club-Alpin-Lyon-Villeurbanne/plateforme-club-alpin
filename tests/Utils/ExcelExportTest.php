<?php

namespace App\Tests\Utils;

use App\Entity\User;
use App\Utils\ExcelExport;
use PHPUnit\Framework\TestCase;

class ExcelExportTest extends TestCase
{
    private ExcelExport $excelExport;

    protected function setUp(): void
    {
        $this->excelExport = new ExcelExport();
    }

    /**
     * Helper method to access private methods.
     */
    private function invokePrivateMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($object::class);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * @dataProvider dateProvider
     */
    public function testGetYearsSinceDate($input, $expectedYears): void
    {
        $result = $this->invokePrivateMethod($this->excelExport, 'getYearsSinceDate', [$input]);
        $this->assertEquals($expectedYears, $result);
    }

    public function dateProvider(): array
    {
        $currentYear = (int) date('Y');

        return [
            'DateTime object' => [
                new \DateTime("$currentYear-01-01"),
                0,
            ],
            'DateTime object 20 years ago' => [
                new \DateTime(($currentYear - 20) . '-01-01'),
                20,
            ],
            'Unix timestamp' => [
                strtotime("$currentYear-01-01"),
                0,
            ],
            'Unix timestamp 10 years ago' => [
                strtotime(($currentYear - 10) . '-01-01'),
                10,
            ],
            'Date string' => [
                "$currentYear-01-01",
                0,
            ],
            'Date string 5 years ago' => [
                ($currentYear - 5) . '-01-01',
                5,
            ],
        ];
    }

    public function testExport(): void
    {
        $title = 'Test Export';
        $rsm = ['ID', 'Name', 'Status'];

        $user = $this->createMock(User::class);
        $user->method('getCiv')->willReturn('M.');
        $user->method('getLastname')->willReturn('DOE');
        $user->method('getFirstname')->willReturn('John');
        $user->method('getBirthday')->willReturn(631182937);
        $user->method('getCafnum')->willReturn('12345');
        $user->method('getDateAdhesion')->willReturn(1672562818);
        $user->method('getTel')->willReturn('0123456789');
        $user->method('getTel2')->willReturn('0987654321');
        $user->method('getEmail')->willReturn('john.doe@example.com');

        $liste = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getUser', 'getStatus', 'getRole'])
            ->getMock();

        $liste->method('getUser')->willReturn($user);
        $liste->method('getStatus')->willReturn(1);
        $liste->method('getRole')->willReturn('Member');

        $datas = [['liste' => $liste]];
        ob_start();
        $response = $this->excelExport->export($title, $datas, $rsm, $title);
        $response->send(); // Force execution
        $output = ob_get_clean();

        $this->assertNotEmpty($output, 'Excel output should not be empty');
        $this->assertStringContainsString('PK', $output, 'Excel output should be a valid XLSX file');
    }

    public function testExportWithEmptyData(): void
    {
        $title = 'Empty Export';
        $rsm = ['ID', 'Name', 'Status'];
        $datas = [];

        try {
            ob_start();
            $this->excelExport->export($title, $datas, $rsm, $title);
            ob_end_clean();
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('Exception should not be thrown with empty data: ' . $e->getMessage());
        }
    }

    /**
     * @dataProvider invalidDateProvider
     */
    public function testGetYearsSinceDateWithInvalidInput($invalidInput): void
    {
        $this->expectException(\Exception::class);
        $this->invokePrivateMethod($this->excelExport, 'getYearsSinceDate', [$invalidInput]);
    }

    public function invalidDateProvider(): array
    {
        return [
            'null value' => [null],
            'invalid string' => ['not a date'],
            'invalid timestamp' => ['abc123'],
            'future date string' => ['2050-01-01'],
            'boolean value' => [true],
            'array value' => [[]],
        ];
    }
}
