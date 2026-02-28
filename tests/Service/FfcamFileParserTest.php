<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\FfcamFileParser;
use PHPUnit\Framework\TestCase;

class FfcamFileParserTest extends TestCase
{
    private string $validLine = <<<LINE
123456;7890;3456;;0;0;1990-05-20;2022-06-15;;DOE;John;1 Rue Test;Complément;75000;Paris;75000;Paris;;;;;;;;;;;;;;0000-00-00;;;;;;;;;;;;;;;;;;;
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

    public function testParseDiscoveryLineUsesPortableAsTel()
    {
        $fields = array_fill(0, 24, '');
        $fields[0] = 'D123456';       // cafnum
        $fields[1] = '24';            // durée (heures)
        $fields[2] = '2099-01-01';    // date adhésion
        $fields[3] = '08:00';         // heure
        $fields[4] = '1990-05-20';    // date naissance
        $fields[5] = 'M';             // civ
        $fields[6] = 'DOE';           // nom
        $fields[7] = 'Jane';          // prénom
        $fields[14] = '0101010101';   // tél domicile
        $fields[17] = '0606060606';   // portable

        $filePath = tempnam(sys_get_temp_dir(), 'ffcam_test_');
        file_put_contents($filePath, implode(';', $fields) . \PHP_EOL);

        $parser = new FfcamFileParser();
        $user = $parser->parse($filePath, 'discovery')->current();

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('0606060606', $user->getTel());

        unlink($filePath);
    }

    public function testParseDiscoveryLineFallsBackToTelDomicile()
    {
        $fields = array_fill(0, 24, '');
        $fields[0] = 'D123456';       // cafnum
        $fields[1] = '24';            // durée (heures)
        $fields[2] = '2099-01-01';    // date adhésion
        $fields[3] = '08:00';         // heure
        $fields[4] = '1990-05-20';    // date naissance
        $fields[5] = 'M';             // civ
        $fields[6] = 'DOE';           // nom
        $fields[7] = 'Jane';          // prénom
        $fields[14] = '0101010101';   // tél domicile
        // $fields[17] reste vide     // portable absent

        $filePath = tempnam(sys_get_temp_dir(), 'ffcam_test_');
        file_put_contents($filePath, implode(';', $fields) . \PHP_EOL);

        $parser = new FfcamFileParser();
        $user = $parser->parse($filePath, 'discovery')->current();

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('0101010101', $user->getTel());

        unlink($filePath);
    }

    public function testParseLineUsesPortableAsTel()
    {
        $fields = array_fill(0, 33, '');
        $fields[0] = '123456';        // cafnum (numérique)
        $fields[1] = '7890';          // club (numérique)
        $fields[2] = '3456';          // num (numérique)
        $fields[6] = '1990-05-20';    // date naissance
        $fields[7] = '2022-06-15';    // date adhésion
        $fields[9] = 'DOE';           // nom
        $fields[10] = 'John';         // prénom
        $fields[27] = '0606060606';   // portable
        $fields[29] = '0101010101';   // tél domicile
        $fields[30] = '0000-00-00';   // date radiation

        $filePath = tempnam(sys_get_temp_dir(), 'ffcam_test_');
        file_put_contents($filePath, implode(';', $fields) . \PHP_EOL);

        $parser = new FfcamFileParser();
        $user = $parser->parse($filePath)->current();

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('0606060606', $user->getTel());

        unlink($filePath);
    }

    public function testParseLineFallsBackToTelDomicile()
    {
        $fields = array_fill(0, 33, '');
        $fields[0] = '123456';        // cafnum (numérique)
        $fields[1] = '7890';          // club (numérique)
        $fields[2] = '3456';          // num (numérique)
        $fields[6] = '1990-05-20';    // date naissance
        $fields[7] = '2022-06-15';    // date adhésion
        $fields[9] = 'DOE';           // nom
        $fields[10] = 'John';         // prénom
        // $fields[27] reste vide     // portable absent
        $fields[29] = '0101010101';   // tél domicile
        $fields[30] = '0000-00-00';   // date radiation

        $filePath = tempnam(sys_get_temp_dir(), 'ffcam_test_');
        file_put_contents($filePath, implode(';', $fields) . \PHP_EOL);

        $parser = new FfcamFileParser();
        $user = $parser->parse($filePath)->current();

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('0101010101', $user->getTel());

        unlink($filePath);
    }
}
