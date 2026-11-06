<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme\Forms;

use TaborovaJizda\Theme\ExternalDatabase;
use TaborovaJizda\Theme\Season\Season;

final class NewsletterHandler
{
    public function __construct(private FormResponse $response)
    {
    }

    public function subscribe(): never
    {
        $email = sanitize_email($this->post('newsletter-email'));
        if ($email === '') {
            $this->response->redirect('error', 10);
        }
        if (! is_email($email)) {
            $this->response->redirect('error', 8);
        }
        $this->response->simulateLocally('subscribe');

        $database = new ExternalDatabase();
        $connection = $database->connection();
        $token = bin2hex(random_bytes(10));
        $statement = $connection->prepare('INSERT INTO newsletter (email, validate_id) VALUES (?, ?)');
        $statement->bind_param('ss', $email, $token);
        if (! $statement->execute()) {
            if ($statement->errno !== 1062) {
                $this->response->redirect('error', 9);
            }
            $statement = $connection->prepare('SELECT is_validated FROM newsletter WHERE email = ?');
            $statement->bind_param('s', $email);
            $statement->execute();
            $row = $statement->get_result()->fetch_row();
            $this->response->redirect('error', empty($row[0]) ? 12 : 11, ['email' => $email]);
        }
        wp_mail(Season::current()->contactEmail(), 'Nová registrace odběru novinek', "Zaregistrován odběr. \nE-mail: $email");
        $this->sendValidationEmail($email, $token);
        $this->response->redirect('success', 3);
    }

    public function validate(): never
    {
        $token = $this->request('validate');
        if ($token === '') {
            $this->response->plain('Nepodařilo se ověřit e-mail.');
        }
        $this->response->simulateLocally('validate');
        $database = new ExternalDatabase();
        $statement = $database->connection()->prepare('UPDATE newsletter SET is_validated = TRUE WHERE validate_id = ?');
        $statement->bind_param('s', $token);
        $statement->execute();
        $this->response->plain($statement->affected_rows === 1 ? 'Podařilo se ověřit e-mail. Můžete zavřít okno prohlížeče.' : 'Nepodařilo se ověřit e-mail.');
    }

    public function unsubscribe(): never
    {
        $token = $this->request('unsubscribe');
        if ($token === '') {
            $this->response->plain('Nepodařilo provést odhlášení. Zkontrolujte prosím adresu odkazu. Nebo kontaktujte správce.');
        }
        $this->response->simulateLocally('unsubscribe');
        $database = new ExternalDatabase();
        $statement = $database->connection()->prepare('DELETE FROM newsletter WHERE validate_id = ?');
        $statement->bind_param('s', $token);
        $statement->execute();
        $this->response->plain($statement->affected_rows === 1 ? 'Váš e-mail byl odstraněn z databáze.' : 'Nepodařilo provést odhlášení. Zkontrolujte prosím adresu odkazu. Nebo kontaktujte správce.');
    }

    public function resend(): never
    {
        $email = sanitize_email($this->request('validation_resend'));
        if (! is_email($email)) {
            $this->response->redirect('error', 13);
        }
        $this->response->simulateLocally('resend');
        $database = new ExternalDatabase();
        $statement = $database->connection()->prepare('SELECT validate_id FROM newsletter WHERE email = ? AND is_validated = FALSE');
        $statement->bind_param('s', $email);
        $statement->execute();
        $row = $statement->get_result()->fetch_row();
        if (! $row) {
            $this->response->redirect('error', 14);
        }
        wp_mail(Season::current()->contactEmail(), 'Žádost o znovuověření', "Zpráva zaslána na e-mail: $email");
        $this->sendValidationEmail($email, (string) $row[0]);
        $this->response->redirect('success', 4);
    }

    private function sendValidationEmail(string $email, string $token): void
    {
        $validateUrl = add_query_arg(['action' => 'tj_newsletter_validate', 'token' => $token], home_url('/odeslat-dotaz/'));
        $unsubscribeUrl = add_query_arg(['action' => 'tj_newsletter_unsubscribe', 'token' => $token], home_url('/odeslat-dotaz/'));
        $message = "Právě jste se registrovali k odběru novinek na webové stránce www.taborovajizda.cz.\n\n" .
            "Pro dokončení je nutné ověřit váš e-mail na následujícím odkaze: \n$validateUrl \n\n" .
            "Pokud si již nebudete přát dostávat upozornění nebo si myslíte, že byl Váš e-mail použit neoprávněně, použijte následující odkaz k odhlášení odběru: \n$unsubscribeUrl \n\n" .
            "Díky za zájem.\nVaše Táborová jízda\n\nNa tento e-mail prosím neodpovídejte.";
        $contactEmail = Season::current()->contactEmail();
        wp_mail($email, 'Registrace na www.taborovajizda.cz', $message, "From: Táborová jízda <$contactEmail>\r\nContent-Type: text/plain; charset=UTF-8");
    }

    private function post(string $key): string
    {
        return isset($_POST[$key]) ? sanitize_text_field(wp_unslash((string) $_POST[$key])) : '';
    }

    private function request(string $key): string
    {
        return isset($_REQUEST[$key]) ? sanitize_text_field(wp_unslash((string) $_REQUEST[$key])) : '';
    }
}
