<?php

namespace BenTools\WebPushBundle\Sender;

use BenTools\WebPushBundle\Model\Message\PushMessage;
use BenTools\WebPushBundle\Model\Response\PushResponse;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise;
use Minishlink\WebPush\Encryption;
use Minishlink\WebPush\VAPID;
use Psr\Http\Message\ResponseInterface;

class PushMessageSender implements PushMessagerSenderInterface
{
    const DEFAULT_TIMEOUT = 30;

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
     * PushMessageSender constructor.
     */
    public function __construct(
        array $auth = [],
        array $defaultOptions = [],
        ?ClientInterface $client = null
    ) {
        if (isset($auth['VAPID'])) {
            $auth['VAPID']['validated'] = false;
        }
        $this->auth = $auth;
        $this->setDefaultOptions($defaultOptions);
        $this->client = $client ?? new Client();
        $this->requestBuilder = new RequestBuilder();
    }

    /**
     * @return PushResponse[]
     *
     * @throws \ErrorException
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function push(PushMessage $message, iterable $subscriptions): iterable
    {
        /** @var UserSubscriptionInterface[] $subscriptions */
        $promises = [];

        if (isset($this->auth['VAPID']) && empty($this->auth['VAPID']['validated'])) {
            $this->auth['VAPID'] = VAPID::validate($this->auth['VAPID']) + ['validated' => true];
        }

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

            $promises[$subscriptionHash] = $this->client->sendAsync($request, ['timeout' => self::DEFAULT_TIMEOUT])
                ->then(function (ResponseInterface $response) use ($subscription) {
                    return new PushResponse($subscription, $response->getStatusCode());
                })
                ->otherwise(function (\Throwable $reason) use ($subscription) {
                    if ($reason instanceof RequestException && $reason->hasResponse()) {
                        return new PushResponse($subscription, $reason->getResponse()->getStatusCode());
                    }

                    throw $reason;
                })
            ;
        }

        $promise = Promise\Utils::settle($promises)
            ->then(function ($results) {
                foreach ($results as $subscriptionHash => $promise) {
                    yield $subscriptionHash => $promise['value'] ?? $promise['reason'];
                }
            })
        ;

        return $promise->wait();
    }

    public function isAutomaticPadding(): bool
    {
        return 0 !== $this->maxPaddingLength;
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
     * @throws \Exception
     */
    public function setMaxPaddingLength($maxPaddingLength): self
    {
        if ($maxPaddingLength > Encryption::MAX_PAYLOAD_LENGTH) {
            throw new \Exception('Automatic padding is too large. Max is '.Encryption::MAX_PAYLOAD_LENGTH.'. Recommended max is '.Encryption::MAX_COMPATIBILITY_PAYLOAD_LENGTH.' for compatibility reasons (see README).');
        } elseif ($maxPaddingLength < 0) {
            throw new \Exception('Padding length should be positive or zero.');
        } elseif (true === $maxPaddingLength) {
            $this->maxPaddingLength = Encryption::MAX_COMPATIBILITY_PAYLOAD_LENGTH;
        } elseif (false === $maxPaddingLength) {
            $this->maxPaddingLength = 0;
        } else {
            $this->maxPaddingLength = $maxPaddingLength;
        }

        return $this;
    }

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
