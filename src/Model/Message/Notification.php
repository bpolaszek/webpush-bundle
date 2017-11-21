<?php

namespace BenTools\WebPushBundle\Model\Message;

class Notification implements JsonPayloadInterface, \ArrayAccess
{

    private $data = [];

    /**
     * @inheritDoc
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return json_encode(['notification' => $this]);
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->data[$offset] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
}
