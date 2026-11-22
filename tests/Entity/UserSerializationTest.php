<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserSerializationTest extends TestCase
{
    public function testSerializeAndUnserialize(): void
    {
        $user = new User(42);
        $user->setEmail('test@example.com');
        $user->setMdp('hashed_password');

        $serialized = serialize($user);
        $restored = unserialize($serialized);

        $this->assertSame(42, $restored->getId());
        $this->assertSame('test@example.com', $restored->getEmail());
        $this->assertSame('hashed_password', $restored->getMdp());
    }

    public function testUnserializeLegacyFormat(): void
    {
        // Simulate native PHP serialization format where $id is a string
        // (bigint returned by Doctrine) and keys are mangled with class name.
        $prefix = "\0" . User::class . "\0";
        $legacyData = [
            $prefix . 'id' => '12345',
            $prefix . 'email' => 'legacy@example.com',
            $prefix . 'mdp' => 'old_hash',
        ];

        $user = (new \ReflectionClass(User::class))->newInstanceWithoutConstructor();
        $user->__unserialize($legacyData);

        $this->assertSame(12345, $user->getId());
        $this->assertSame('legacy@example.com', $user->getEmail());
        $this->assertSame('old_hash', $user->getMdp());
    }

    public function testUnserializeWithNullId(): void
    {
        $user = new User();
        $user->setEmail('noid@example.com');
        $user->setMdp('pwd');

        $serialized = serialize($user);
        $restored = unserialize($serialized);

        $this->assertNull($restored->getId());
        $this->assertSame('noid@example.com', $restored->getEmail());
    }
}
