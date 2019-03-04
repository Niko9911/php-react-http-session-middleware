<?php

declare(strict_types=1);

/**
 *
 * NOTICE OF LICENSE
 *
 * This source file is released under MIT license by Niko Granö.
 *
 * @copyright Niko Granö <niko9911@ironlions.fi> (https://granö.fi)
 *
 */

namespace Niko9911\React\Middleware\Session;

final class Session implements \JsonSerializable
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var array
     */
    private $contents;

    /**
     * @var Id
     */
    private $sessionId;

    /**
     * @var string[]
     */
    private $oldIds = [];

    /**
     * @var int
     */
    private $status = \PHP_SESSION_NONE;

    /**
     * @param string $id
     * @param array  $contents
     * @param Id     $sessionId
     */
    public function __construct(string $id, array $contents, Id $sessionId)
    {
        $this->id = $id;
        $this->contents = $contents;
        $this->sessionId = $sessionId;

        if ('' !== $this->id) {
            $this->status = \PHP_SESSION_ACTIVE;
        }
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param array $contents
     */
    public function setContents(array $contents): void
    {
        $this->contents = $contents;
    }

    /**
     * @return array
     */
    public function getContents(): array
    {
        return $this->contents;
    }

    /**
     * @return string[]
     */
    public function getOldIds(): array
    {
        return $this->oldIds;
    }

    /**
     * @return bool
     */
    public function begin(): bool
    {
        if (\PHP_SESSION_ACTIVE === $this->status) {
            return true;
        }

        $this->status = \PHP_SESSION_ACTIVE;

        if ('' === $this->id) {
            $this->id = $this->sessionId->generate();
        }

        return true;
    }

    /**
     * @return bool
     */
    public function end(): bool
    {
        if (\PHP_SESSION_NONE === $this->status) {
            return true;
        }

        $this->oldIds[] = $this->id;
        $this->status = \PHP_SESSION_NONE;
        $this->id = '';
        $this->contents = [];

        return true;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return \PHP_SESSION_ACTIVE === $this->status;
    }

    public function regenerate(): bool
    {
        // Can only regenerate active sessions
        if (\PHP_SESSION_ACTIVE !== $this->status) {
            return false;
        }

        $this->oldIds[] = $this->id;
        $this->id = $this->sessionId->generate();

        return true;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id'       => $this->id,
            'contents' => $this->contents,
            'oldIds'   => $this->oldIds,
            'status'   => $this->status,
        ];
    }

    /**
     * @param array $session
     * @param bool  $clone
     *
     * @throws \InvalidArgumentException
     *
     * @return Session
     */
    public function fromArray(array $session, bool $clone = true): self
    {
        if (!isset($session['id'], $session['contents'], $session['oldIds'], $session['oldIds'])) {
            throw new \InvalidArgumentException('Session array most contain "id", "contents", "oldIds", and "status".');
        }

        $self = $this;
        if ($clone) {
            $self = clone $this;
        }
        $self->id = $session['id'];
        $self->contents = $session['contents'];
        $self->oldIds = $session['oldIds'];
        $self->status = $session['status'];

        return $self;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @see https://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource
     *
     * @since 5.4.0
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
