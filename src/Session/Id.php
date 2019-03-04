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

interface Id
{
    /**
     * Generate a random string to be used as sessions ID.
     *
     * @return string
     */
    public function generate(): string;
}
