<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme\Forms;

use TaborovaJizda\Theme\ExternalDatabase;
use TaborovaJizda\Theme\Season\Season;

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
        if (! is_string($path) || trim($path, '/') !== 'odeslat-dotaz') {
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
        $this->redirect('error', 102);
    }

    private function dispatch(string $type, ?string $legacyKey = null): never
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_REQUEST['action'])) {
            check_admin_referer('tj_form_submit', 'tj_nonce');
        }

        $this->normaliseToken($type, $legacyKey);

        try {
            match ($type) {
                'registration' => $this->registration(),
                'contact' => $this->contact(),
                'subscribe' => $this->subscribe(),
                'validate' => $this->validate(),
                'unsubscribe' => $this->unsubscribe(),
                'resend' => $this->resend(),
                default => $this->redirect('error', 102),
            };
        } catch (\Throwable $error) {
            error_log('[taborovajizda] Form processing failed: ' . $error->getMessage());
            $this->redirect('error', 101);
        }
    }

    private function registration(): never
    {
        $required = ['jmeno', 'prijmeni', 'datum_nar', 'rodne_c', 'adresa', 'nastup', 'vystup', 'kontakt_email'];
        foreach ($required as $field) {
            if ($this->post($field) === '') {
                $this->redirect('error', 2);
            }
        }

        $email = sanitize_email($this->post('kontakt_email'));
        if (! is_email($email)) {
            $this->redirect('error', 1);
        }

        $personalId = $this->post('rodne_c');
        if (! $this->validPersonalId($personalId)) {
            $this->redirect('error', 15);
        }
        $this->simulateLocally('registration');

        $database = new ExternalDatabase();
        $connection = $database->connection();
        $season = Season::current();
        $seasonKey = $season->externalSeasonKey();

        if ($seasonKey !== null) {
            $statement = $connection->prepare('SELECT * FROM tabory WHERE id = ? LIMIT 1');
            $id = (int) $seasonKey;
            $statement->bind_param('i', $id);
            $statement->execute();
            $result = $statement->get_result();
        } else {
            $result = $connection->query('SELECT * FROM tabory ORDER BY id DESC LIMIT 1');
        }
        $camp = $result?->fetch_assoc();
        if (! is_array($camp)) {
            $this->redirect('error', 3);
        }

        $values = [
            'firstName' => $this->post('jmeno'),
            'lastName' => $this->post('prijmeni'),
            'birthDate' => $this->post('datum_nar'),
            'personalId' => $personalId,
            'address' => $this->post('adresa'),
            'campId' => (int) $camp['id'],
            'roommate' => $this->nullablePost('spolubydlici'),
            'arrival' => $this->post('nastup'),
            'departure' => $this->post('vystup'),
            'contactName' => $this->nullablePost('kontakt_jmeno'),
            'contactPhone' => $this->nullablePost('kontakt_tel', true),
            'email' => $email,
            'note' => $this->nullablePost('dotaz'),
        ];

        $statement = $connection->prepare(
            'INSERT INTO prihlasky (jmeno, prijmeni, datum_nar, rc, adresa, id_tabora, spolubydlici, nastup, vystup, kontakt_jmeno, kontakt_tel, kontakt_email, poznamka) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->bind_param(
            'sssssisssssss',
            $values['firstName'], $values['lastName'], $values['birthDate'], $values['personalId'], $values['address'],
            $values['campId'], $values['roommate'], $values['arrival'], $values['departure'], $values['contactName'],
            $values['contactPhone'], $values['email'], $values['note']
        );
        if (! $statement->execute()) {
            $this->redirect('error', $statement->errno === 1062 ? 5 : 4);
        }

        $seasonData = $season->jsonSerialize();
        $price = (int) ($seasonData['price']['amount'] ?? $camp['cena']);
        $start = $season->startDate()?->format('j. n.') ?? date_create_from_format('!Y-m-d', $camp['termin_od'])->format('j. n.');
        $end = $season->endDate()?->format('j. n.') ?? date_create_from_format('!Y-m-d', $camp['termin_do'])->format('j. n.');
        $birthDate = date_create_from_format('!Y-m-d', $values['birthDate']);
        $fullText = $season->registrationStatus() === 'full'
            ? "Omlouváme se. TÁBOR JE JIŽ PLNĚ OBSAZEN!\r\nVaše přihláška bude zařazena do seznamu náhradníku, pokud se uvolní místo, ozveme se. TÁBOR ZATÍM NEPLAŤTE! \r\nDěkujeme za zájem i za pochopení.\r\n\r\n"
            : '';
        $message = "Dobrý den,\ntoto je automaticky vygenerovaný e-mail ze stránek https://taborovajizda.cz a byl Vám odeslán, protože Vaše adresa byla uvedena jako kontaktní e-mail v elektronické přihlášce na letní dětský tábor \"{$season->title()}\"\r\n\r\n" .
            $fullText .
            "Jméno dítěte: {$values['firstName']} {$values['lastName']}\r\nDatum narození: {$birthDate->format('j. n. Y')}\r\nRodné číslo: {$values['personalId']}\r\nAdresa bydliště: {$values['address']}\r\n" .
            'Kontakt jméno: ' . ($values['contactName'] ?? ' -') . "\r\nKontakt telefon: " . ($values['contactPhone'] ?? ' -') . "\r\nKontakt e-mail: {$values['email']}\r\n" .
            'S kým chci být ve stanu: ' . ($values['roommate'] ?? ' -') . "\r\nKde se k vám připojím: {$values['arrival']}\r\nZ tábora s Vámi pojedu: {$values['departure']}\r\n" .
            "Doplňující informace:\r\n" . ($values['note'] ?? '') . "\r\n\r\nTábor se koná v areálu Plátěná osada v termínu $start – $end {$season->year()}. Cena tábora je $price Kč. Za každého dalšího sourozence dítěte však zaplatíte pouze " . ($price - 500) . " Kč. " .
            "Platbu proveďte nejpozději do konce *května* na účet 27-3094190207/0100 a jako variabilní symbol uveďte rodné číslo svého dítěte. Pokud provádíte platbu pro více dětí najednou (sourozenci), použijte rodné číslo jednoho z nich.\r\n\r\n" .
            "Rádi Vám vystavíme fakturu pro zaměstnavatele, pokud chcete čerpat příspěvěk z FKSP nebo potvrzení pro pojišťovnu o absolvování tábora\r\n\r\n" .
            "V den odjezdu nezapomeňte potřebné dokumenty (potvrzení o zdravotní způsobilosti, potvrzení bezinfekčnosti, kartičku zdravotní pojišťovny, souhlas s využitím mediálních materiálů pro propagaci tábora, zmocnění pro zdravotníka)\r\n\r\n" .
            "Přejeme krásný den a těšíme se v srpnu na viděnou\r\nTým Táborové jízdy.";
        $subjectSuffix = $season->registrationStatus() === 'full' ? ' (TÁBOR JIŽ OBSAZEN)' : '';
        wp_mail($email, "Elektronická přihláška na tábor \"{$season->title()}\"$subjectSuffix", $message, [
            'From: Táborová jízda <info@taborovajizda.cz>',
            'Content-Type: text/plain; charset=UTF-8',
            'Cc: Táborová jízda <info@taborovajizda.cz>',
        ]);
        $this->redirect('success', 1);
    }

    private function contact(): never
    {
        if ($this->post('jmeno') === '' || $this->post('email') === '' || $this->post('dotaz') === '') {
            $this->redirect('error', 7);
        }
        if (! is_email($this->post('email'))) {
            $this->redirect('error', 6);
        }
        $this->simulateLocally('contact');
        $this->redirect('success', 2);
    }

    private function subscribe(): never
    {
        $email = sanitize_email($this->post('newsletter-email'));
        if ($email === '') {
            $this->redirect('error', 10);
        }
        if (! is_email($email)) {
            $this->redirect('error', 8);
        }
        $this->simulateLocally('subscribe');

        $database = new ExternalDatabase();
        $connection = $database->connection();
        $token = bin2hex(random_bytes(10));
        $statement = $connection->prepare('INSERT INTO newsletter (email, validate_id) VALUES (?, ?)');
        $statement->bind_param('ss', $email, $token);
        if (! $statement->execute()) {
            if ($statement->errno !== 1062) {
                $this->redirect('error', 9);
            }
            $statement = $connection->prepare('SELECT is_validated FROM newsletter WHERE email = ?');
            $statement->bind_param('s', $email);
            $statement->execute();
            $row = $statement->get_result()->fetch_row();
            $this->redirect('error', empty($row[0]) ? 12 : 11, ['email' => $email]);
        }

        wp_mail('info@taborovajizda.cz', 'Nová registrace odběru novinek', "Zaregistrován odběr. \nE-mail: $email");
        $this->sendValidationEmail($email, $token);
        $this->redirect('success', 3);
    }

    private function validate(): never
    {
        $token = $this->request('validate');
        if ($token === '') {
            $this->plainResponse('Nepodařilo se ověřit e-mail.');
        }
        $this->simulateLocally('validate');
        $database = new ExternalDatabase();
        $statement = $database->connection()->prepare('UPDATE newsletter SET is_validated = TRUE WHERE validate_id = ?');
        $statement->bind_param('s', $token);
        $statement->execute();
        $this->plainResponse($statement->affected_rows === 1 ? 'Podařilo se ověřit e-mail. Můžete zavřít okno prohlížeče.' : 'Nepodařilo se ověřit e-mail.');
    }

    private function unsubscribe(): never
    {
        $token = $this->request('unsubscribe');
        if ($token === '') {
            $this->plainResponse('Nepodařilo provést odhlášení. Zkontrolujte prosím adresu odkazu. Nebo kontaktujte správce.');
        }
        $this->simulateLocally('unsubscribe');
        $database = new ExternalDatabase();
        $statement = $database->connection()->prepare('DELETE FROM newsletter WHERE validate_id = ?');
        $statement->bind_param('s', $token);
        $statement->execute();
        $this->plainResponse($statement->affected_rows === 1 ? 'Váš e-mail byl odstraněn z databáze.' : 'Nepodařilo provést odhlášení. Zkontrolujte prosím adresu odkazu. Nebo kontaktujte správce.');
    }

    private function resend(): never
    {
        $email = sanitize_email($this->request('validation_resend'));
        if (! is_email($email)) {
            $this->redirect('error', 13);
        }
        $this->simulateLocally('resend');
        $database = new ExternalDatabase();
        $statement = $database->connection()->prepare('SELECT validate_id FROM newsletter WHERE email = ? AND is_validated = FALSE');
        $statement->bind_param('s', $email);
        $statement->execute();
        $row = $statement->get_result()->fetch_row();
        if (! $row) {
            $this->redirect('error', 14);
        }
        wp_mail('info@taborovajizda.cz', 'Žádost o znovuověření', "Zpráva zaslána na e-mail: $email");
        $this->sendValidationEmail($email, (string) $row[0]);
        $this->redirect('success', 4);
    }

    private function sendValidationEmail(string $email, string $token): void
    {
        $message = "Právě jste se registrovali k odběru novinek na webové stránce www.taborovajizda.cz.\n\n" .
            "Pro dokončení je nutné ověřit váš e-mail na následujícím odkaze: \n" . add_query_arg(['action' => 'tj_newsletter_validate', 'token' => $token], home_url('/odeslat-dotaz/')) . " \n\n" .
            "Pokud si již nebudete přát dostávat upozornění nebo si myslíte, že byl Váš e-mail použit neoprávněně, použijte následující odkaz k odhlášení odběru: \n" .
            add_query_arg(['action' => 'tj_newsletter_unsubscribe', 'token' => $token], home_url('/odeslat-dotaz/')) . " \n\nDíky za zájem.\nVaše Táborová jízda\n\nNa tento e-mail prosím neodpovídejte.";
        wp_mail($email, 'Registrace na www.taborovajizda.cz', $message, "From: Táborová jízda <info@taborovajizda.cz>\r\nContent-Type: text/plain; charset=UTF-8");
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

    private function simulateLocally(string $type): void
    {
        if (wp_get_environment_type() === 'production') {
            return;
        }
        $payload = [];
        foreach ($_POST as $key => $value) {
            $payload[$key] = in_array($key, ['rodne_c', 'datum_nar'], true) ? '[redacted]' : sanitize_text_field(wp_unslash((string) $value));
        }
        error_log('[taborovajizda] Local form simulation: ' . wp_json_encode(['type' => $type, 'payload' => $payload]));
        if (in_array($type, ['validate', 'unsubscribe'], true)) {
            $this->plainResponse('Lokální simulace proběhla úspěšně. Produkční databáze nebyla změněna.');
        }
        $codes = ['registration' => 1, 'contact' => 2, 'subscribe' => 3, 'resend' => 4];
        $this->redirect('success', $codes[$type] ?? 1);
    }

    private function validPersonalId(string $value): bool
    {
        if (! preg_match('#^\s*(\d\d)(\d\d)(\d\d)[ /]*(\d\d\d)(\d?)\s*$#', $value, $matches)) {
            return false;
        }
        [, $year, $month, $day, $extension, $checksum] = $matches;
        $year = (int) $year;
        $month = (int) $month;
        $day = (int) $day;
        if ($checksum === '') {
            $year += $year < 54 ? 1900 : 1800;
        } else {
            $modulo = (int) (($matches[1] . $matches[2] . $matches[3] . $extension) % 11);
            if (($modulo === 10 ? 0 : $modulo) !== (int) $checksum) {
                return false;
            }
            $year += $year < 54 ? 2000 : 1900;
        }
        if ($month > 70 && $year > 2003) {
            $month -= 70;
        } elseif ($month > 50) {
            $month -= 50;
        } elseif ($month > 20 && $year > 2003) {
            $month -= 20;
        }
        return checkdate($month, $day, $year);
    }

    private function post(string $key): string
    {
        return isset($_POST[$key]) ? sanitize_text_field(wp_unslash((string) $_POST[$key])) : '';
    }

    private function nullablePost(string $key, bool $removeSpaces = false): ?string
    {
        $value = $key === 'dotaz' && isset($_POST[$key])
            ? sanitize_textarea_field(wp_unslash((string) $_POST[$key]))
            : $this->post($key);
        if ($removeSpaces) {
            $value = preg_replace('/\s+/', '', $value) ?? '';
        }
        return $value === '' ? null : $value;
    }

    private function request(string $key): string
    {
        return isset($_REQUEST[$key]) ? sanitize_text_field(wp_unslash((string) $_REQUEST[$key])) : '';
    }

    private function redirect(string $kind, int $code, array $extra = []): never
    {
        wp_safe_redirect(add_query_arg([$kind => $code] + $extra, wp_get_referer() ?: home_url('/')));
        exit;
    }

    private function plainResponse(string $message): never
    {
        status_header(200);
        header('Content-Type: text/plain; charset=UTF-8');
        echo esc_html($message);
        exit;
    }
}
