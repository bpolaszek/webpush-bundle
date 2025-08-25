<?php

namespace BenTools\WebPushBundle\Action;

use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

final class RegisterSubscriptionAction
{
    /**
     * RegisterSubscriptionAction constructor.
     */
    public function __construct(private readonly UserSubscriptionManagerRegistry $registry)
    {
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    private function subscribe(UserInterface $user, string $subscriptionHash, array $subscription, array $options = []): void
    {
        $manager = $this->registry->getManager($user);
        $userSubscription = $manager->getUserSubscription($user, $subscriptionHash)
        or $userSubscription = $manager->factory($user, $subscriptionHash, $subscription, $options);
        $manager->save($userSubscription);
    }

    /**
     * @throws BadRequestHttpException
     * @throws \RuntimeException
     */
    private function unsubscribe(UserInterface $user, string $subscriptionHash): void
    {
        $manager = $this->registry->getManager($user);
        $subscription = $manager->getUserSubscription($user, $subscriptionHash);
        if (null === $subscription) {
            throw new BadRequestHttpException('Subscription hash not found');
        }
        $manager->delete($subscription);
    }

    public function __invoke(Request $request, ?UserInterface $user = null): Response
    {
        if (null === $user) {
            throw new AccessDeniedHttpException('Not authenticated.');
        }

        if (!in_array($request->getMethod(), ['POST', 'DELETE'])) {
            throw new MethodNotAllowedHttpException(['POST', 'DELETE']);
        }

        $data = json_decode($request->getContent(), true);
        $subscription = $data['subscription'] ?? [];
        $options = $data['options'] ?? [];

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new BadRequestHttpException(json_last_error_msg());
        }

        if (!isset($subscription['endpoint'])) {
            throw new BadRequestHttpException('Invalid subscription object.');
        }

        $manager = $this->registry->getManager($user);
        $subscriptionHash = $manager->hash($subscription['endpoint'], $user);

        if ('DELETE' === $request->getMethod()) {
            $this->unsubscribe($user, $subscriptionHash);
        } else {
            $this->subscribe($user, $subscriptionHash, $subscription, $options);
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
