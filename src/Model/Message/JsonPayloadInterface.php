<?php

namespace BenTools\WebPushBundle\Model\Message;

interface JsonPayloadInterface extends PayloadInterface, \JsonSerializable
{

    /**
     * @return array
     */
    public function jsonSerialize(): array;

    /**
     * The JSON representation of the current object (usually json_encode($this))
     *
     * @return string
     */
    public function __toString(): string;
}
