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
     * @param string        $deviceHash
     * @param array         $subscription
     * @throws \RuntimeException
     */
    private function processSubscription(UserInterface $user, string $deviceHash, array $subscription)
    {
        $manager = $this->registry->getManagerFor($user);
        $device = $manager->getUserDevice($user, $deviceHash)
        or $device = $manager->factory($user, $deviceHash, $subscription);
        $manager->save($device);
    }

    /**
     * @param UserInterface $user
     * @param string        $deviceHash
     * @throws BadRequestHttpException
     * @throws \RuntimeException
     */
    private function processUnsubscription(UserInterface $user, string $deviceHash)
    {
        $manager = $this->registry->getManagerFor($user);
        $device = $manager->getUserDevice($user, $deviceHash);
        if (null === $device) {
            throw new BadRequestHttpException("Device hash not found");
        }
        $manager->delete($device);
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

        $data = json_decode($request->getContent(), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new BadRequestHttpException(json_last_error_msg());
        }

        if (!isset($data['deviceHash'])) {
            throw new BadRequestHttpException('deviceHash has not been provided.');
        }

        if ('POST' === $request->getMethod() && !isset($data['subscription'])) {
            throw new BadRequestHttpException('subscription has not been provided.');
        }

        if ('DELETE' === $request->getMethod()) {
            $this->processUnsubscription($user, $data['deviceHash']);
        } else {
            $this->processSubscription($user, $data['deviceHash'], $data['subscription']);
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
