<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme\Forms;

use TaborovaJizda\Theme\IndexPermalinks;

final class Forms
{
    private const ACTIONS = [
        'tj_registration_submit' => 'registration',
        'tj_contact_submit' => 'contact',
    ];

    private FormResponse $response;
    private RegistrationHandler $registration;
    private ContactHandler $contact;

    public function __construct()
    {
        $this->response = new FormResponse();
        $this->registration = new RegistrationHandler($this->response, new PersonalIdValidator());
        $this->contact = new ContactHandler($this->response);
    }

    public function register(): void
    {
        foreach (self::ACTIONS as $action => $type) {
            add_action('admin_post_' . $action, fn () => $this->dispatch($type));
            add_action('admin_post_nopriv_' . $action, fn () => $this->dispatch($type));
        }
        add_action('template_redirect', [$this, 'routeFormPage'], 0);
    }

    /** The registration form posts to /odeslat-dotaz/ instead of admin-post.php. */
    public function routeFormPage(): void
    {
        $path = wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        if (! is_string($path) || IndexPermalinks::relativeRequestPath($path) !== 'odeslat-dotaz') {
            return;
        }

        $action = $this->request('action');
        if (isset(self::ACTIONS[$action])) {
            $this->dispatch(self::ACTIONS[$action]);
        }
        $this->response->redirect('error', 102);
    }

    private function dispatch(string $type): never
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_REQUEST['action'])) {
            check_admin_referer('tj_form_submit', 'tj_nonce');
        }
        try {
            match ($type) {
                'registration' => $this->registration->handle(),
                'contact' => $this->contact->handle(),
                default => $this->response->redirect('error', 102),
            };
        } catch (\Throwable $error) {
            error_log('[taborovajizda] Form processing failed: ' . $error->getMessage());
            $this->response->redirect('error', 101);
        }
    }

    private function request(string $key): string
    {
        return isset($_REQUEST[$key]) ? sanitize_text_field(wp_unslash((string) $_REQUEST[$key])) : '';
    }
}
