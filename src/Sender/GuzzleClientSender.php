<?php

namespace BenTools\WebPushBundle\Sender;

use Base64Url\Base64Url;
use BenTools\WebPushBundle\Model\Message\WebPushMessage;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use BenTools\WebPushBundle\Model\WebPushResponse;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Request;
use Minishlink\WebPush\Encryption;
use Minishlink\WebPush\Utils;
use Minishlink\WebPush\VAPID;
use Psr\Http\Message\RequestInterface;
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
    private $automaticPadding = Encryption::MAX_COMPATIBILITY_PAYLOAD_LENGTH;

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
    public function __construct(ClientInterface $client, array $auth = [], array $defaultOptions = [], ?int $timeout = 30)
    {
        if (ini_get('mbstring.func_overload') >= 2) {
            trigger_error("[WebPush] mbstring.func_overload is enabled for str* functions. You must disable it if you want to send push notifications with payload or use VAPID. You can fix this in your php.ini.", E_USER_NOTICE);
        }

        if (isset($auth['VAPID'])) {
            $auth['VAPID'] = VAPID::validate($auth['VAPID']);
        }

        $this->client = $client;
        $this->auth = $auth;
        $this->setDefaultOptions($defaultOptions);
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
    public function push(WebPushMessage $message, iterable $subscriptions, array $options = [], array $auth = [], &$promise = null): void
    {
        /** @var UserSubscriptionInterface[] $subscriptions */
        $promises = [];
        foreach ($subscriptions as $subscription) {

            $subscriptionHash = $subscription->getSubscriptionHash();
            $request = $this->createRequest($message, $subscription);

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


    private function createRequest(WebPushMessage $message, UserSubscriptionInterface $subscription): RequestInterface
    {
        $endpoint = $subscription->getEndpoint();
        $payload = $message->getPayload();
        $options = $message->getOptions() + $this->defaultOptions;
        $auth = $message->getAuth() + $this->auth;

        if (null !== $payload && null !== $subscription->getPublicKey() && null !== $subscription->getAuthToken()) {

            if (Utils::safeStrlen($payload) > Encryption::MAX_PAYLOAD_LENGTH) {
                throw new \ErrorException('Size of payload must not be greater than '.Encryption::MAX_PAYLOAD_LENGTH.' bytes.');
            }

            $payload = Encryption::padPayload($payload, $this->automaticPadding, $subscription->getContentEncoding());
            $encrypted = Encryption::encrypt($payload, $subscription->getPublicKey(), $subscription->getAuthToken(), $subscription->getContentEncoding());

            $headers = [
                'Content-Type' => 'application/octet-stream',
                'Content-Encoding' => $subscription->getContentEncoding(),
            ];

            if ('aesgcm' === $subscription->getContentEncoding()) {
                $headers['Encryption'] = 'salt='.Base64Url::encode($encrypted['salt']);
                $headers['Crypto-Key'] = 'dh='.Base64Url::encode($encrypted['localPublicKey']);
            }

            $encryptionContentCodingHeader = Encryption::getContentCodingHeader($encrypted['salt'], $encrypted['localPublicKey'], $subscription->getContentEncoding());
            $content = $encryptionContentCodingHeader.$encrypted['cipherText'];

            $headers['Content-Length'] = Utils::safeStrlen($content);
        } else {
            $headers = [
                'Content-Length' => 0,
            ];

            $content = '';
        }

        $headers['TTL'] = $options['TTL'] ?? 0;

        if (isset($options['urgency'])) {
            $headers['Urgency'] = $options['urgency'];
        }

        if (isset($options['topic'])) {
            $headers['Topic'] = $options['topic'];
        }

        // if GCM
        if (substr($endpoint, 0, strlen(self::GCM_URL)) === self::GCM_URL) {
            if (array_key_exists('GCM', $auth)) {
                $headers['Authorization'] = 'key='.$auth['GCM'];
            } else {
                throw new \ErrorException('No GCM API Key specified.');
            }
        } // if VAPID (GCM doesn't support it but FCM does)
        elseif (array_key_exists('VAPID', $auth)) {
            $vapid = $auth['VAPID'];

            $audience = parse_url($endpoint, PHP_URL_SCHEME).'://'.parse_url($endpoint, PHP_URL_HOST);

            if (!parse_url($audience)) {
                throw new \ErrorException('Audience "'.$audience.'"" could not be generated.');
            }

            $vapidHeaders = VAPID::getVapidHeaders($audience, $vapid['subject'], $vapid['publicKey'], $vapid['privateKey'], $subscription->getContentEncoding());

            $headers['Authorization'] = $vapidHeaders['Authorization'];

            if ('aesgcm' === $subscription->getContentEncoding()) {
                if (array_key_exists('Crypto-Key', $headers)) {
                    $headers['Crypto-Key'] .= ';'.$vapidHeaders['Crypto-Key'];
                } else {
                    $headers['Crypto-Key'] = $vapidHeaders['Crypto-Key'];
                }
            } else if ('aes128gcm' === $subscription->getContentEncoding() && substr($endpoint, 0, strlen(self::FCM_BASE_URL)) === self::FCM_BASE_URL) {
                $endpoint = str_replace('fcm/send', 'wp', $endpoint);
            }
        }

        return new Request('POST', $endpoint, $headers, $content);
    }

    /**
     * @return bool
     */
    public function isAutomaticPadding(): bool
    {
        return $this->automaticPadding !== 0;
    }

    /**
     * @return int
     */
    public function getAutomaticPadding()
    {
        return $this->automaticPadding;
    }

    /**
     * @param int|bool $automaticPadding Max padding length
     *
     * @return self
     *
     * @throws \Exception
     */
    public function setAutomaticPadding($automaticPadding): self
    {
        if ($automaticPadding > Encryption::MAX_PAYLOAD_LENGTH) {
            throw new \Exception('Automatic padding is too large. Max is '.Encryption::MAX_PAYLOAD_LENGTH.'. Recommended max is '.Encryption::MAX_COMPATIBILITY_PAYLOAD_LENGTH.' for compatibility reasons (see README).');
        } elseif ($automaticPadding < 0) {
            throw new \Exception('Padding length should be positive or zero.');
        } elseif ($automaticPadding === true) {
            $this->automaticPadding = Encryption::MAX_COMPATIBILITY_PAYLOAD_LENGTH;
        } elseif ($automaticPadding === false) {
            $this->automaticPadding = 0;
        } else {
            $this->automaticPadding = $automaticPadding;
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
        $this->defaultOptions['TTL'] = isset($defaultOptions['TTL']) ? $defaultOptions['TTL'] : 0;
        $this->defaultOptions['urgency'] = isset($defaultOptions['urgency']) ? $defaultOptions['urgency'] : null;
        $this->defaultOptions['topic'] = isset($defaultOptions['topic']) ? $defaultOptions['topic'] : null;
        $this->defaultOptions['batchSize'] = isset($defaultOptions['batchSize']) ? $defaultOptions['batchSize'] : 1000;
    }
}
