<?php

namespace BenTools\WebPushBundle\Sender;

use BenTools\WebPushBundle\Model\Message\WebPushMessage;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use BenTools\WebPushBundle\Model\WebPushResponse;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise;
use Minishlink\WebPush\Encryption;
use Minishlink\WebPush\VAPID;
use Psr\Http\Message\ResponseInterface;

class GuzzleClientSender implements WebPushNotificationSenderInterface
{
    public const GCM_URL = 'https://android.googleapis.com/gcm/send';
    public const FCM_BASE_URL = 'https://fcm.googleapis.com';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    private $auth;

    /**
     * @var array Default options : TTL, urgency, topic, batchSize
     */
    private $defaultOptions;

    /**
     * @var int Automatic padding of payloads, if disabled, trade security for bandwidth
     */
    private $maxPaddingLength = Encryption::MAX_COMPATIBILITY_PAYLOAD_LENGTH;

    /**
     * @var RequestBuilder
     */
    private $requestBuilder;

    /**
     * WebPush constructor.
     *
     * @param array    $auth           Some servers needs authentication
     * @param array    $defaultOptions TTL, urgency, topic, batchSize
     * @param int|null $timeout        Timeout of POST request
     * @param array    $clientOptions
     *
     * @throws \ErrorException
     */
    public function __construct(
        ClientInterface $client,
        array $auth = [],
        array $defaultOptions = [],
        RequestBuilder $requestBuilder = null
    ) {

        if (ini_get('mbstring.func_overload') >= 2) {
            trigger_error("[WebPush] mbstring.func_overload is enabled for str* functions. You must disable it if you want to send push notifications with payload or use VAPID. You can fix this in your php.ini.", E_USER_NOTICE);
        }

        if (isset($auth['VAPID'])) {
            $auth['VAPID'] = VAPID::validate($auth['VAPID']);
        }

        $this->client = $client;
        $this->auth = $auth;
        $this->setDefaultOptions($defaultOptions);
        $this->requestBuilder = $requestBuilder ?? new RequestBuilder();
    }

    /**
     * @param null|string $payload
     * @param             $subscriptions
     * @param array       $options
     * @param array       $auth
     * @return mixed
     * @throws \ErrorException
     * @throws \LogicException
     */
    public function push(WebPushMessage $message, iterable $subscriptions, &$promise = null): void
    {
        /** @var UserSubscriptionInterface[] $subscriptions */
        $promises = [];
        foreach ($subscriptions as $subscription) {
            $subscriptionHash = $subscription->getSubscriptionHash();
            $auth = $message->getAuth() + $this->auth;

            $request = $this->requestBuilder->createRequest(
                $message,
                $subscription,
                $message->getOption('TTL') ?? $this->defaultOptions['TTL'],
                $this->maxPaddingLength
            );

            if (isset($auth['VAPID'])) {
                $request = $this->requestBuilder->withVAPIDAuthentication($request, $auth['VAPID'], $subscription);
            } elseif (isset($auth['GCM'])) {
                $request = $this->requestBuilder->withGCMAuthentication($request, $auth['GCM']);
            }

            $promises[$subscriptionHash] = $this->client->sendAsync($request)
                ->then(function (ResponseInterface $response) use ($subscriptionHash) {
                    return new WebPushResponse($subscriptionHash, $response->getStatusCode());
                })
                ->otherwise(function (\Throwable $reason) use ($subscriptionHash) {

                    if ($reason instanceof RequestException && $reason->hasResponse()) {
                        return new WebPushResponse($subscriptionHash, $reason->getResponse()->getStatusCode());
                    }

                    throw $reason;
                })
            ;
        }

        $promise = Promise\settle($promises)
            ->then(function ($results) {
                foreach ($results as $subscriptionHash => $promise) {
                    yield $subscriptionHash => $promise['value'] ?? $promise['reason'];
                }
            })
        ;

        $promise->wait();
    }

    /**
     * @return bool
     */
    public function isAutomaticPadding(): bool
    {
        return $this->maxPaddingLength !== 0;
    }

    /**
     * @return int
     */
    public function getMaxPaddingLength()
    {
        return $this->maxPaddingLength;
    }

    /**
     * @param int|bool $maxPaddingLength Max padding length
     *
     * @return self
     *
     * @throws \Exception
     */
    public function setMaxPaddingLength($maxPaddingLength): self
    {
        if ($maxPaddingLength > Encryption::MAX_PAYLOAD_LENGTH) {
            throw new \Exception('Automatic padding is too large. Max is '.Encryption::MAX_PAYLOAD_LENGTH.'. Recommended max is '.Encryption::MAX_COMPATIBILITY_PAYLOAD_LENGTH.' for compatibility reasons (see README).');
        } elseif ($maxPaddingLength < 0) {
            throw new \Exception('Padding length should be positive or zero.');
        } elseif ($maxPaddingLength === true) {
            $this->maxPaddingLength = Encryption::MAX_COMPATIBILITY_PAYLOAD_LENGTH;
        } elseif ($maxPaddingLength === false) {
            $this->maxPaddingLength = 0;
        } else {
            $this->maxPaddingLength = $maxPaddingLength;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getDefaultOptions(): array
    {
        return $this->defaultOptions;
    }

    /**
     * @param array $defaultOptions Keys 'TTL' (Time To Live, defaults 0), 'urgency', 'topic', 'batchSize'
     */
    public function setDefaultOptions(array $defaultOptions)
    {
        $this->defaultOptions['TTL'] = $defaultOptions['TTL'] ?? 0;
        $this->defaultOptions['urgency'] = $defaultOptions['urgency'] ?? null;
        $this->defaultOptions['topic'] = $defaultOptions['topic'] ?? null;
        $this->defaultOptions['batchSize'] = $defaultOptions['batchSize'] ?? 1000;
    }
}
