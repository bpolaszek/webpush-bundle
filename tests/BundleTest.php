<?php

namespace BenTools\WebPushBundle\Tests;

use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionManagerRegistry;
use BenTools\WebPushBundle\Tests\Classes\TestUser;
use BenTools\WebPushBundle\Tests\Classes\TestUserSubscriptionManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class BundleTest extends KernelTestCase
{
    protected function setUp(): void
    {
        static::bootKernel();
    }

    /**
     * @test
     */
    public function parameters_are_set(): void
    {
        $this->assertEquals('this_is_a_private_key', self::getContainer()->getParameter('bentools_webpush.vapid_private_key'));
        $this->assertEquals('this_is_a_public_key', self::getContainer()->getParameter('bentools_webpush.vapid_public_key'));
        $this->assertTrue(self::getContainer()->has(UserSubscriptionManagerRegistry::class));
    }

    /**
     * @test
     */
    public function manager_is_found(): void
    {
        // Find by class name
        $this->assertInstanceOf(TestUserSubscriptionManager::class, self::getContainer()->get(UserSubscriptionManagerRegistry::class)->getManager(TestUser::class));

        // Find by object
        $this->assertInstanceOf(TestUserSubscriptionManager::class, self::getContainer()->get(UserSubscriptionManagerRegistry::class)->getManager(new TestUser('foo')));
    }

    /**
     * @test
     */
    public function unknown_manager_raises_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        self::getContainer()->get(UserSubscriptionManagerRegistry::class)->getManager(Foo::class);
    }
}
