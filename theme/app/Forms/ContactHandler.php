<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme\Forms;

use TaborovaJizda\Theme\Season\Season;

final class ContactHandler
{
    public function __construct(private FormResponse $response)
    {
    }

    public function handle(): never
    {
        $name = $this->post('jmeno');
        $email = $this->post('email');
        $question = $this->post('dotaz', true);

        if ($name === '' || $email === '' || $question === '') {
            $this->response->redirect('error', 7);
        }
        if (! is_email($email)) {
            $this->response->redirect('error', 6);
        }

        $this->response->simulateLocally('contact');

        $subject = sprintf('Dotaz z webu Táborové jízdy od %s', $name);
        $message = sprintf("Jméno: %s\nE-mail: %s\n\nDotaz:\n%s", $name, $email, $question);
        $headers = ['Reply-To: ' . $name . ' <' . $email . '>'];
        if (! wp_mail(Season::current()->contactEmail(), $subject, $message, $headers)) {
            $this->response->redirect('error', 101);
        }

        $this->response->redirect('success', 2);
    }

    private function post(string $key, bool $multiline = false): string
    {
        if (! isset($_POST[$key])) {
            return '';
        }

        $value = wp_unslash((string) $_POST[$key]);
        return $multiline ? sanitize_textarea_field($value) : sanitize_text_field($value);
    }
}
