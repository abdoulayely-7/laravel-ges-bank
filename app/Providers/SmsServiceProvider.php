<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Sms\SmsSenderInterface;
use App\Services\Sms\TwilioSmsSender;

class SmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SmsSenderInterface::class, TwilioSmsSender::class);
    }

    public function boot(): void
    {
        //
    }
}
