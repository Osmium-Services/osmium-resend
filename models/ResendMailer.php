<?php

declare(strict_types=1);

namespace Osmium\Services\Resend\Models;

use Osmium\Core\Library\PlainTextMailer;

/**
 * Sends mail via Resend's email API (JSON POST with a bearer API key). Plain
 * curl, so there is no SDK to install.
 *
 * Built by MailerFactory with the site config, which supplies the shared
 * From address and name; the API key comes from this service's own config.
 * Resend only sends from an address on a verified domain, so the From
 * address on the Email settings page must be on one.
 */
class ResendMailer implements PlainTextMailer
{
    private const SEND_URL = 'https://api.resend.com/emails';
    private const USER_AGENT = 'osmium-resend/1.0';

    public function __construct(private object $config) {}

    /**
     * @param array<int, array{email: string, name?: string}> $recipients
     * @return array{success: bool, error?: string}
     */
    public function send(array $recipients, string $subject, string $htmlBody, ?string $textBody = null): array
    {
        try {
            $this->postMessage(
                recipients: $recipients,
                subject: $subject,
                htmlBody: $htmlBody,
                textBody: $textBody,
            );
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @param array<int, array{email: string, name?: string}> $recipients
     */
    private function postMessage(array $recipients, string $subject, string $htmlBody, ?string $textBody): void
    {
        $notReady = !ResendConfig::isReady();
        if ($notReady) throw new \RuntimeException('Resend is not configured: set the API key on the Resend settings page.');

        $email = $this->config->email;
        $fromName = $email->fromName ?? '';
        $from = $fromName !== '' ? "{$fromName} <{$email->fromAddress}>" : $email->fromAddress;

        $to = \array_map(
            fn (array $recipient) => isset($recipient['name'])
                ? "{$recipient['name']} <{$recipient['email']}>"
                : $recipient['email'],
            $recipients,
        );

        $message = [
            'from' => $from,
            'to' => $to,
            'subject' => $subject,
            'html' => $htmlBody,
        ];
        $hasText = $textBody !== null;
        if ($hasText) $message['text'] = $textBody;
        $payload = \json_encode($message);

        $ch = \curl_init(self::SEND_URL);
        \curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . ResendConfig::get()->apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = \curl_exec($ch);
        $curlError = \curl_error($ch);
        $httpCode = \curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($curlError) throw new \RuntimeException("Resend send curl error: {$curlError}");

        $failed = $httpCode < 200 || $httpCode >= 300;
        if ($failed) {
            $decoded = \json_decode(json: (string) $response, associative: true);
            $error = $decoded['message'] ?? ($response ?: "HTTP {$httpCode}");
            throw new \RuntimeException("Resend send failed ({$httpCode}): {$error}");
        }
    }
}
