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

namespace Niko9911\React\Middleware\Session\Id;

use Niko9911\React\Middleware\Session\Id;

final class Random implements Id
{
    public const DEFAULT_LENGTH = 16;

    /**
     * @var int
     */
    private $length;
    /**
     * @var string
     */
    private $prefix;

    /**
     * @param int    $length
     * @param string $prefix
     */
    public function __construct(int $length = self::DEFAULT_LENGTH, string $prefix = 'sess-')
    {
        $this->length = $length;
        $this->prefix = $prefix;
    }

    /**
     * Generate a random string to be used as sessions ID.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function generate(): string
    {
        return $this->prefix.\bin2hex(\random_bytes($this->length));
    }
}
