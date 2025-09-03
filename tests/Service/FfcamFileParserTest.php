<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\FfcamFileParser;
use PHPUnit\Framework\TestCase;

class FfcamFileParserTest extends TestCase
{
    private string $validLine = <<<LINE
123456;7890;3456;;0;0;1990-05-20;2022-06-15;;DOE;John;1 Rue Test;Complément;75000;Paris;75000;Paris;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
LINE;

    private string $invalidLine = 'invalid;line;data;;;;';

    public function testParseValidLine()
    {
        $filePath = tempnam(sys_get_temp_dir(), 'ffcam_test_');
        file_put_contents($filePath, $this->validLine . \PHP_EOL);

        $parser = new FfcamFileParser();
        $generator = $parser->parse($filePath);
        $user = $generator->current();

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John', $user->getFirstname());
        $this->assertEquals('DOE', $user->getLastname());
        $this->assertEquals('75000', $user->getCp());
        $this->assertEquals('Paris', $user->getVille());

        unlink($filePath);
    }

    public function testParseInvalidLineSkipped()
    {
        $filePath = tempnam(sys_get_temp_dir(), 'ffcam_test_');
        file_put_contents($filePath, $this->invalidLine . \PHP_EOL . $this->validLine . \PHP_EOL);

        $parser = new FfcamFileParser();
        $users = iterator_to_array($parser->parse($filePath));

        // Should yield only one valid user
        $this->assertCount(1, $users);
        $this->assertEquals('John', $users[0]->getFirstname());

        unlink($filePath);
    }

    public function testParseThrowsOnUnreadableFile()
    {
        $parser = new FfcamFileParser();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Can't open");

        $parser->parse('/path/to/nonexistent/file.txt')->current();
    }

    public function testValidateLineThrowsOnBadData()
    {
        $parser = new FfcamFileParser();
        $method = new \ReflectionMethod($parser, 'validateLine');
        $method->setAccessible(true);

        $invalidLine = array_fill(0, 33, ''); // empty columns
        $invalidLine[0] = 'abc'; // not numeric
        $invalidLine[1] = 'def'; // not numeric
        $invalidLine[2] = 'ghi'; // not numeric
        $invalidLine[6] = 'bad-date'; // bad date format

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Multiple values are wrong');

        $method->invoke($parser, $invalidLine, 1);
    }

    public function testValidateLineThrowsOnMissingColumns()
    {
        $parser = new FfcamFileParser();
        $method = new \ReflectionMethod($parser, 'validateLine');
        $method->setAccessible(true);

        $tooShortLine = array_fill(0, 10, '');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid format');

        $method->invoke($parser, $tooShortLine, 2);
    }
}
