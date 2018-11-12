<?php

namespace BenTools\WebPushBundle\Tests;

use BenTools\WebPushBundle\Registry\WebPushManagerRegistry;
use BenTools\WebPushBundle\Tests\Classes\TestUser;
use BenTools\WebPushBundle\Tests\Classes\TestUserSubscriptionManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class BundleTest extends KernelTestCase
{
    protected function setUp()
    {
        static::bootKernel();
    }

    /**
     * @test
     */
    public function parameters_are_set()
    {
        $this->assertEquals('this_is_a_private_key', self::$kernel->getContainer()->getParameter('bentools_webpush.private_key'));
        $this->assertEquals('this_is_a_public_key', self::$kernel->getContainer()->getParameter('bentools_webpush.public_key'));
        $this->assertTrue(self::$kernel->getContainer()->has(WebPushManagerRegistry::class));
    }

    /**
     * @test
     */
    public function manager_is_found()
    {
        $this->assertInstanceOf(TestUserSubscriptionManager::class, self::$kernel->getContainer()->get(WebPushManagerRegistry::class)->getManager(TestUser::class));
        $this->assertInstanceOf(TestUserSubscriptionManager::class, self::$kernel->getContainer()->get(WebPushManagerRegistry::class)->getManager(new TestUser('foo')));
    }
}
