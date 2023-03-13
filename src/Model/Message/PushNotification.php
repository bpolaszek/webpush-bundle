<?php

namespace BenTools\WebPushBundle\Model\Message;

use ArrayAccess;
use JsonSerializable;

use function is_array;

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
     * PushNotification constructor.
     */
    public function __construct(
        private ?string $title,
         private array $options = [],
    ) {}

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

    public function setOption(string $key, mixed $value): void
    {
        if (null === $value) {
            unset($this->options[$key]);

            return;
        }

        $this->options[$key] = $value;
    }

    public function getOption(string $key): mixed
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
            'options' => self::sanitize($this->options),
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
    public function offsetExists(mixed $offset): bool
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
    public function offsetGet(mixed $offset): mixed
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
    public function offsetSet(mixed $offset, mixed $value): void
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
    public function offsetUnset(mixed $offset): void
    {
        unset($this->options[$offset]);
    }

    private static function sanitize(mixed $input): mixed
    {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                if (null === $value) {
                    unset($input[$key]);
                }
                if (is_array($value)) {
                    $input[$key] = self::sanitize($input[$key]);
                }
            }
        }

        return $input;
    }
}
