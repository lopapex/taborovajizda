<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme\Forms;

use TaborovaJizda\Theme\IndexPermalinks;

final class Forms
{
    private const ACTIONS = [
        'tj_registration_submit' => 'registration',
        'tj_contact_submit' => 'contact',
        'tj_newsletter_subscribe' => 'subscribe',
        'tj_newsletter_validate' => 'validate',
        'tj_newsletter_unsubscribe' => 'unsubscribe',
        'tj_newsletter_resend' => 'resend',
    ];

    private const LEGACY_KEYS = [
        'eprihlaska' => 'registration',
        'dotaz' => 'contact',
        'newsletter' => 'subscribe',
        'validate' => 'validate',
        'unsubscribe' => 'unsubscribe',
        'validation_resend' => 'resend',
    ];

    private FormResponse $response;
    private RegistrationHandler $registration;
    private ContactHandler $contact;
    private NewsletterHandler $newsletter;

    public function __construct()
    {
        $this->response = new FormResponse();
        $this->registration = new RegistrationHandler($this->response, new PersonalIdValidator());
        $this->contact = new ContactHandler($this->response);
        $this->newsletter = new NewsletterHandler($this->response);
    }

    public function register(): void
    {
        foreach (self::ACTIONS as $action => $type) {
            add_action('admin_post_' . $action, fn () => $this->dispatch($type));
            add_action('admin_post_nopriv_' . $action, fn () => $this->dispatch($type));
        }
        add_action('template_redirect', [$this, 'routeLegacyRequest'], 0);
    }

    public function routeLegacyRequest(): void
    {
        $path = wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        if (! is_string($path) || IndexPermalinks::relativeRequestPath($path) !== 'odeslat-dotaz') {
            return;
        }

        $action = $this->request('action');
        if (isset(self::ACTIONS[$action])) {
            $this->dispatch(self::ACTIONS[$action]);
        }
        foreach (self::LEGACY_KEYS as $key => $type) {
            if (isset($_REQUEST[$key])) {
                $this->dispatch($type, $key);
            }
        }
        $this->response->redirect('error', 102);
    }

    private function dispatch(string $type, ?string $legacyKey = null): never
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_REQUEST['action'])) {
            check_admin_referer('tj_form_submit', 'tj_nonce');
        }
        $this->normaliseToken($type, $legacyKey);

        try {
            match ($type) {
                'registration' => $this->registration->handle(),
                'contact' => $this->contact->handle(),
                'subscribe' => $this->newsletter->subscribe(),
                'validate' => $this->newsletter->validate(),
                'unsubscribe' => $this->newsletter->unsubscribe(),
                'resend' => $this->newsletter->resend(),
                default => $this->response->redirect('error', 102),
            };
        } catch (\Throwable $error) {
            error_log('[taborovajizda] Form processing failed: ' . $error->getMessage());
            $this->response->redirect('error', 101);
        }
    }

    private function normaliseToken(string $type, ?string $legacyKey): void
    {
        $key = match ($type) {
            'validate' => 'validate',
            'unsubscribe' => 'unsubscribe',
            'resend' => 'validation_resend',
            default => null,
        };
        if ($key === null) {
            return;
        }
        if (isset($_REQUEST['token'])) {
            $_REQUEST[$key] = sanitize_text_field(wp_unslash((string) $_REQUEST['token']));
        } elseif ($legacyKey !== null && isset($_REQUEST[$legacyKey])) {
            $_REQUEST[$key] = sanitize_text_field(wp_unslash((string) $_REQUEST[$legacyKey]));
        } elseif ($type === 'resend' && isset($_REQUEST['email'])) {
            $_REQUEST[$key] = sanitize_email(wp_unslash((string) $_REQUEST['email']));
        }
    }

    private function request(string $key): string
    {
        return isset($_REQUEST[$key]) ? sanitize_text_field(wp_unslash((string) $_REQUEST[$key])) : '';
    }
}
