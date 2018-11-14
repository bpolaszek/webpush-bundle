<?php

namespace BenTools\WebPushBundle\Tests;

use BenTools\DoctrineStatic\ManagerRegistry;
use BenTools\WebPushBundle\Action\RegisterSubscriptionAction;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionManagerRegistry;
use BenTools\WebPushBundle\Tests\Classes\TestUser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

final class RegistrationTest extends KernelTestCase
{
    protected function setUp()
    {
        static::bootKernel();
    }

    /**
     * @test
     */
    public function registration_works()
    {
        /** @var ManagerRegistry $persistence */
        $persistence = self::$kernel->getContainer()->get('doctrine');
        $registry = self::$kernel->getContainer()->get(UserSubscriptionManagerRegistry::class);
        $em = $persistence->getManagerForClass(TestUser::class);
        $bob = new TestUser('bob');
        $em->persist($bob);
        $em->flush();
        $this->assertNotNull($em->find(TestUser::class, 'bob'));


        $register = self::$kernel->getContainer()->get(RegisterSubscriptionAction::class);

        $rawSubscriptionData = [
            'subscription' => [
                'endpoint' => 'http://foo.bar',
                'keys'     => [
                    'p256dh' => 'bob_public_key',
                    'auth'   => 'bob_private_key',
                ]
            ]
        ];

        $request = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'POST'], json_encode($rawSubscriptionData));
        $register($request, $bob);

        $subscriptions = $registry->getManager($bob)->findByUser($bob);
        $this->assertCount(1, $subscriptions);

        $request = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'DELETE'], json_encode($rawSubscriptionData));
        $register($request, $bob);

        $subscriptions = $registry->getManager($bob)->findByUser($bob);
        $this->assertCount(0, $subscriptions);
    }
}
