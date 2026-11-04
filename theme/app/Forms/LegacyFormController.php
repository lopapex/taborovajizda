<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme\Forms;

final class LegacyFormController
{
    private const ACTIONS = [
        'tj_registration_submit' => 'eprihlaska',
        'tj_contact_submit' => 'dotaz',
        'tj_newsletter_subscribe' => 'newsletter',
        'tj_newsletter_validate' => 'validate',
        'tj_newsletter_unsubscribe' => 'unsubscribe',
        'tj_newsletter_resend' => 'validation_resend',
    ];

    public function __construct(private string $themeRoot)
    {
    }

    public function register(): void
    {
        foreach (self::ACTIONS as $action => $legacyRequestKey) {
            add_action('admin_post_' . $action, fn () => $this->dispatch($legacyRequestKey));
            add_action('admin_post_nopriv_' . $action, fn () => $this->dispatch($legacyRequestKey));
        }

        add_action('template_redirect', [$this, 'routeLegacyRequest'], 0);
    }

    public function routeLegacyRequest(): void
    {
        $path = wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        if (! is_string($path) || trim($path, '/') !== 'odeslat-dotaz') {
            return;
        }

        foreach (array_values(self::ACTIONS) as $requestKey) {
            if (isset($_REQUEST[$requestKey])) {
                $this->dispatch($requestKey);
            }
        }
    }

    private function dispatch(string $legacyRequestKey): never
    {
        if (isset($_REQUEST['action']) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            check_admin_referer('tj_form_submit', 'tj_nonce');
        }

        $this->normaliseToken($legacyRequestKey);

        if ($this->isLocalEnvironment()) {
            $this->handleLocalRequest($legacyRequestKey);
        }

        $_REQUEST[$legacyRequestKey] = $_REQUEST[$legacyRequestKey] ?? '1';
        require $this->themeRoot . '/app/Legacy/FormProcessor.php';
        exit;
    }

    private function normaliseToken(string $legacyRequestKey): void
    {
        if (! in_array($legacyRequestKey, ['validate', 'unsubscribe', 'validation_resend'], true)) {
            return;
        }

        if (isset($_REQUEST['token']) && ! isset($_REQUEST[$legacyRequestKey])) {
            $_REQUEST[$legacyRequestKey] = sanitize_text_field(wp_unslash($_REQUEST['token']));
        }

        if ($legacyRequestKey === 'validation_resend' && isset($_REQUEST['email'])) {
            $_REQUEST[$legacyRequestKey] = sanitize_email(wp_unslash($_REQUEST['email']));
        }
    }

    private function isLocalEnvironment(): bool
    {
        return function_exists('wp_get_environment_type') && wp_get_environment_type() !== 'production';
    }

    private function handleLocalRequest(string $legacyRequestKey): never
    {
        $payload = [];
        foreach ($_POST as $key => $value) {
            if (in_array($key, ['rodne_c', 'datum_nar'], true)) {
                $payload[$key] = '[redacted]';
                continue;
            }
            $payload[$key] = is_scalar($value) ? sanitize_text_field(wp_unslash((string) $value)) : '[complex value]';
        }

        error_log('[taborovajizda] Local form simulation: ' . wp_json_encode([
            'type' => $legacyRequestKey,
            'payload' => $payload,
        ]));

        if (in_array($legacyRequestKey, ['validate', 'unsubscribe'], true)) {
            wp_die(
                esc_html__('Lokální simulace proběhla úspěšně. Produkční databáze nebyla změněna.', 'taborovajizda'),
                esc_html__('Lokální test', 'taborovajizda'),
                ['response' => 200]
            );
        }

        $successCodes = [
            'eprihlaska' => 1,
            'dotaz' => 2,
            'newsletter' => 3,
            'validation_resend' => 4,
        ];
        $target = wp_get_referer() ?: home_url('/');
        wp_safe_redirect(add_query_arg('success', $successCodes[$legacyRequestKey] ?? 1, $target));
        exit;
    }
}
