<?php

namespace BenTools\WebPushBundle\Action;

use BenTools\HelpfulTraits\Symfony\SecurityAwareTrait;
use BenTools\WebPushBundle\Registry\WebPushManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class SubscriptionAction
{
    use SecurityAwareTrait;

    /**
     * @var WebPushManagerRegistry
     */
    private $registry;

    /**
     * RegisterSubscriptionAction constructor.
     * @param TokenStorageInterface  $tokenStorage
     * @param WebPushManagerRegistry $registry
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        WebPushManagerRegistry $registry
    ) {

        $this->tokenStorage = $tokenStorage;
        $this->registry = $registry;
    }

    /**
     * @param UserInterface $user
     * @param string        $subscriptionHash
     * @param array         $subscription
     * @throws \RuntimeException
     */
    private function processSubscription(UserInterface $user, string $subscriptionHash, array $subscription)
    {
        $manager = $this->registry->getManager($user);
        $userSubscription = $manager->getUserSubscription($user, $subscriptionHash)
        or $userSubscription = $manager->factory($user, $subscriptionHash, $subscription);
        $manager->save($userSubscription);
    }

    /**
     * @param UserInterface $user
     * @param string        $subscriptionHash
     * @throws BadRequestHttpException
     * @throws \RuntimeException
     */
    private function processUnsubscription(UserInterface $user, string $subscriptionHash)
    {
        $manager = $this->registry->getManager($user);
        $subscription = $manager->getUserSubscription($user, $subscriptionHash);
        if (null === $subscription) {
            throw new BadRequestHttpException("Subscription hash not found");
        }
        $manager->delete($subscription);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws BadRequestHttpException
     * @throws MethodNotAllowedHttpException
     * @throws \LogicException
     * @throws \RuntimeException
     */
    public function __invoke(Request $request): Response
    {

        if (!in_array($request->getMethod(), ['POST', 'DELETE'])) {
            throw new MethodNotAllowedHttpException(['POST', 'DELETE']);
        }

        $user = $this->getUser();

        if (null === $this->getUser()) {
            throw new BadRequestHttpException("User is not logged in.");
        }

        $subscription = json_decode($request->getContent(), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new BadRequestHttpException(json_last_error_msg());
        }

        if (!isset($subscription['endpoint'])) {
            throw new BadRequestHttpException('Invalid subscription object.');
        }

        $manager = $this->registry->getManager($user);
        $subscriptionHash = $manager->hash($subscription['endpoint']);

        if ('DELETE' === $request->getMethod()) {
            $this->processUnsubscription($user, $subscriptionHash);
        } else {
            $this->processSubscription($user, $subscriptionHash, $subscription);
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
