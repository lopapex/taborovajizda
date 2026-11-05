<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme\Forms;

final class ContactHandler
{
    public function __construct(private FormResponse $response)
    {
    }

    public function handle(): never
    {
        if ($this->post('jmeno') === '' || $this->post('email') === '' || $this->post('dotaz') === '') {
            $this->response->redirect('error', 7);
        }
        if (! is_email($this->post('email'))) {
            $this->response->redirect('error', 6);
        }
        $this->response->simulateLocally('contact');
        $this->response->redirect('success', 2);
    }

    private function post(string $key): string
    {
        return isset($_POST[$key]) ? sanitize_text_field(wp_unslash((string) $_POST[$key])) : '';
    }
}
