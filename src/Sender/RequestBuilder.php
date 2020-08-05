<?php

namespace BenTools\WebPushBundle\Sender;

use Base64Url\Base64Url;
use BenTools\WebPushBundle\Model\Message\PushMessage;
use BenTools\WebPushBundle\Model\Subscription\UserSubscriptionInterface;
use GuzzleHttp\Psr7\Request;
use function GuzzleHttp\Psr7\stream_for;
use GuzzleHttp\Psr7\Uri;
use Minishlink\WebPush\Encryption;
use Minishlink\WebPush\Utils;
use Minishlink\WebPush\VAPID;
use Psr\Http\Message\RequestInterface;

final class RequestBuilder
{
    private const FCM_BASE_URL = 'https://fcm.googleapis.com';

    /**
     * @param int $maxPaddingLength
     *
     * @throws \ErrorException
     * @throws \InvalidArgumentException
     */
    public function createRequest(
        PushMessage $message,
        UserSubscriptionInterface $subscription,
        int $ttl = 0,
        $maxPaddingLength = Encryption::MAX_COMPATIBILITY_PAYLOAD_LENGTH
    ): RequestInterface {
        $request = new Request('POST', $subscription->getEndpoint());
        $request = $this->withOptionalHeaders($request, $message);
        $request = $request->withHeader('TTL', $ttl);

        if (null !== $message->getPayload() && null !== $subscription->getPublicKey() && null !== $subscription->getAuthToken()) {
            $request = $request
                ->withHeader('Content-Type', 'application/octet-stream')
                ->withHeader('Content-Encoding', $subscription->getContentEncoding());

            $payload = $this->getNormalizedPayload($message->getPayload(), $subscription->getContentEncoding(), $maxPaddingLength);

            $encrypted = Encryption::encrypt(
                $payload,
                $subscription->getPublicKey(),
                $subscription->getAuthToken(),
                $subscription->getContentEncoding()
            );

            if ('aesgcm' === $subscription->getContentEncoding()) {
                $request = $request->withHeader('Encryption', 'salt='.Base64Url::encode($encrypted['salt']))
                    ->withHeader('Crypto-Key', 'dh='.Base64Url::encode($encrypted['localPublicKey']));
            }

            $encryptionContentCodingHeader = Encryption::getContentCodingHeader($encrypted['salt'], $encrypted['localPublicKey'], $subscription->getContentEncoding());
            $content = $encryptionContentCodingHeader.$encrypted['cipherText'];

            return $request
                ->withBody(stream_for($content))
                ->withHeader('Content-Length', Utils::safeStrlen($content));
        }

        return $request
            ->withHeader('Content-Length', 0);
    }

    /**
     * @throws \ErrorException
     * @throws \InvalidArgumentException
     */
    public function withVAPIDAuthentication(RequestInterface $request, array $vapid, UserSubscriptionInterface $subscription): RequestInterface
    {
        $endpoint = $subscription->getEndpoint();
        $audience = parse_url($endpoint, PHP_URL_SCHEME).'://'.parse_url($endpoint, PHP_URL_HOST);

        if (!parse_url($audience)) {
            throw new \ErrorException('Audience "'.$audience.'"" could not be generated.');
        }

        $vapidHeaders = VAPID::getVapidHeaders($audience, $vapid['subject'], $vapid['publicKey'], $vapid['privateKey'], $subscription->getContentEncoding());

        $request = $request->withHeader('Authorization', $vapidHeaders['Authorization']);

        if ('aesgcm' === $subscription->getContentEncoding()) {
            if ($request->hasHeader('Crypto-Key')) {
                $request = $request->withHeader('Crypto-Key', $request->getHeaderLine('Crypto-Key').';'.$vapidHeaders['Crypto-Key']);
            } else {
                $headers['Crypto-Key'] = $vapidHeaders['Crypto-Key'];
                $request->withHeader('Crypto-Key', $vapidHeaders['Crypto-Key']);
            }
        } elseif ('aes128gcm' === $subscription->getContentEncoding() && self::FCM_BASE_URL === substr($endpoint, 0, strlen(self::FCM_BASE_URL))) {
            $request = $request->withUri(new Uri(str_replace('fcm/send', 'wp', $endpoint)));
        }

        return $request;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function withGCMAuthentication(RequestInterface $request, string $apiKey): RequestInterface
    {
        return $request->withHeader('Authorization', 'key='.$apiKey);
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function withOptionalHeaders(RequestInterface $request, PushMessage $message): RequestInterface
    {
        foreach (['urgency', 'topic'] as $option) {
            if (null !== $message->getOption($option)) {
                $request = $request->withHeader($option, $message->getOption($option));
            }
        }

        return $request;
    }

    /**
     * @param mixed $automaticPadding
     *
     * @throws \ErrorException
     */
    private function getNormalizedPayload(?string $payload, string $contentEncoding, $automaticPadding): ?string
    {
        if (null === $payload) {
            return null;
        }
        if (Utils::safeStrlen($payload) > Encryption::MAX_PAYLOAD_LENGTH) {
            throw new \ErrorException('Size of payload must not be greater than '.Encryption::MAX_PAYLOAD_LENGTH.' bytes.');
        }

        return Encryption::padPayload($payload, $automaticPadding, $contentEncoding);
    }
}
