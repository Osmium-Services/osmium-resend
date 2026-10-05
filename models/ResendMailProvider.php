<?php

declare(strict_types=1);

namespace Osmium\Services\Resend\Models;

/**
 * Adapts Resend to core's mail.providers hook (see ServiceHooks /
 * MailerFactory).
 */
class ResendMailProvider
{
    public const ID = 'resend';

    /**
     * mail.providers - advertise Resend and whether it can send now.
     */
    public static function provider(array $payload): array
    {
        return [
            'id' => self::ID,
            'label' => 'Resend',
            'ready' => ResendConfig::isReady(),
            'settingsRoute' => 'settings/resend/',
            'mailer' => ResendMailer::class,
        ];
    }
}
