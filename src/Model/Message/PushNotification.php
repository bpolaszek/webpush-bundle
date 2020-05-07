<?php

namespace BenTools\WebPushBundle\Model\Message;

use ArrayAccess;
use JsonSerializable;

final class PushNotification implements JsonSerializable, ArrayAccess
{
    const BODY = 'body';
    const ICON = 'icon';
    const IMAGE = 'image';
    const BADGE = 'badge';
    const VIBRATE = 'vibrate';
    const SOUND = 'sound';
    const DIR = 'dir';
    const TAG = 'tag';
    const DATA = 'data';
    const REQUIREINTERACTION = 'requireInteraction';
    const RENOTIFY = 'renotify';
    const SILENT = 'silent';
    const ACTIONS = 'actions';
    const TIMESTAMP = 'timestamp';

    /**
     * @var string|null
     */
    private $title;

    /**
     * @var array
     */
    private $options;

    /**
     * PushNotification constructor.
     */
    public function __construct(?string $title, array $options = [])
    {
        $this->title = $title;
        $this->options = $options;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * @param mixed $value
     */
    public function setOption(string $key, $value): void
    {
        if (null === $value) {
            unset($this->options[$key]);

            return;
        }

        $this->options[$key] = $value;
    }

    public function getOption($key)
    {
        return $this->options[$key] ?? null;
    }

    public function createMessage(array $options = [], array $auth = []): PushMessage
    {
        return new PushMessage((string) $this, $options, $auth);
    }

    public function jsonSerialize(): array
    {
        return [
            'title' => $this->title,
            'options' => array_diff($this->options, array_filter($this->options, 'is_null')),
        ];
    }

    public function __toString(): string
    {
        return (string) json_encode($this);
    }

    /**
     * Whether a offset exists.
     *
     * @see https://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     *
     * @return bool true on success or false on failure.
     *              </p>
     *              <p>
     *              The return value will be casted to boolean if non-boolean was returned.
     *
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->options);
    }

    /**
     * Offset to retrieve.
     *
     * @see https://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     *                      </p>
     *
     * @return mixed can return all value types
     *
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->options[$offset] ?? null;
    }

    /**
     * Offset to set.
     *
     * @see https://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     *                      </p>
     * @param mixed $value  <p>
     *                      The value to set.
     *                      </p>
     *
     * @return void
     *
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->options[$offset] = $value;
    }

    /**
     * Offset to unset.
     *
     * @see https://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     *                      The offset to unset.
     *                      </p>
     *
     * @return void
     *
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        unset($this->options[$offset]);
    }
}
