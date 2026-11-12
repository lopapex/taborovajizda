<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme\Forms;

final class FormResponse
{
    public function simulateLocally(string $type): void
    {
        if (wp_get_environment_type() === 'production') {
            return;
        }
        $payload = [];
        foreach ($_POST as $key => $value) {
            $payload[$key] = in_array($key, ['rodne_c', 'datum_nar'], true)
                ? '[redacted]'
                : sanitize_text_field(wp_unslash((string) $value));
        }
        error_log('[taborovajizda] Local form simulation: ' . wp_json_encode(['type' => $type, 'payload' => $payload]));
        $this->redirect('success', $type === 'contact' ? 2 : 1);
    }

    public function redirect(string $kind, int $code, array $extra = []): never
    {
        wp_safe_redirect(add_query_arg([$kind => $code] + $extra, wp_get_referer() ?: home_url('/')));
        exit;
    }

    public function plain(string $message): never
    {
        status_header(200);
        header('Content-Type: text/plain; charset=UTF-8');
        echo esc_html($message);
        exit;
    }
}
