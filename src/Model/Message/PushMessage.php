<?php

namespace BenTools\WebPushBundle\Model\Message;

/**
 * A message is an enveloppe that contain:
 * - An optional payload
 * - An optional array of options (like TTL, Topic, etc)
 * - An optional array of authentication data (if different from the client).
 */
final class PushMessage
{
    private $payload;
    private $options;
    private $auth;

    /**
     * PushMessage constructor.
     */
    public function __construct(?string $payload = null, array $options = [], array $auth = [])
    {
        $this->payload = $payload;
        $this->options = $options;
        $this->auth = $auth;
    }

    public function setPayload(?string $payload): void
    {
        $this->payload = $payload;
    }

    public function getPayload(): ?string
    {
        return $this->payload;
    }

    public function setTTL(int $ttl): void
    {
        $this->options['TTL'] = $ttl;
    }

    public function setTopic(?string $topic): void
    {
        $this->options['topic'] = $topic;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function setUrgency(?string $urgency): void
    {
        if (null === $urgency) {
            unset($this->options['urgency']);

            return;
        }

        if (!in_array($urgency, ['very-low', 'low', 'normal', 'high'])) {
            throw new \InvalidArgumentException('Urgency must be one of: very-low | low | normal | high');
        }

        $this->options['urgency'] = $urgency;
    }

    public function getOptions(): array
    {
        return array_diff($this->options, array_filter($this->options, 'is_null'));
    }

    public function getOption(string $key)
    {
        return $this->options[$key] ?? null;
    }

    public function getAuth(): array
    {
        return $this->auth;
    }
}
