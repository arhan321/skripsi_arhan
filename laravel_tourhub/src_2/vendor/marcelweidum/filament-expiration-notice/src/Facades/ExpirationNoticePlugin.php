<?php

declare(strict_types=1);

namespace MarcelWeidum\ExpirationNoticePlugin\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \MarcelWeidum\ExpirationNoticePlugin\ExpirationNotice
 */
final class ExpirationNoticePlugin extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \MarcelWeidum\ExpirationNoticePlugin\ExpirationNotice::class;
    }
}
