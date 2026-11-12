<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme\Forms;

use TaborovaJizda\Theme\ExternalDatabase;
use TaborovaJizda\Theme\Season\Season;

final class RegistrationHandler
{
    public function __construct(private FormResponse $response, private PersonalIdValidator $personalIdValidator)
    {
    }

    public function handle(): never
    {
        foreach (['jmeno', 'prijmeni', 'datum_nar', 'rodne_c', 'adresa', 'nastup', 'vystup', 'kontakt_email'] as $field) {
            if ($this->post($field) === '') {
                $this->response->redirect('error', 2);
            }
        }
        $email = sanitize_email($this->post('kontakt_email'));
        if (! is_email($email)) {
            $this->response->redirect('error', 1);
        }
        $personalId = $this->post('rodne_c');
        if (! $this->personalIdValidator->isValid($personalId)) {
            $this->response->redirect('error', 15);
        }
        $this->response->simulateLocally('registration');

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
            $this->response->redirect('error', 3);
        }

        $values = [
            'firstName' => $this->post('jmeno'), 'lastName' => $this->post('prijmeni'),
            'birthDate' => $this->post('datum_nar'), 'personalId' => $personalId,
            'address' => $this->post('adresa'), 'campId' => (int) $camp['id'],
            'roommate' => $this->nullablePost('spolubydlici'), 'arrival' => $this->post('nastup'),
            'departure' => $this->post('vystup'), 'contactName' => $this->nullablePost('kontakt_jmeno'),
            'contactPhone' => $this->nullablePost('kontakt_tel', true), 'email' => $email,
            'note' => $this->nullablePost('dotaz'),
        ];
        $statement = $connection->prepare('INSERT INTO prihlasky (jmeno, prijmeni, datum_nar, rc, adresa, id_tabora, spolubydlici, nastup, vystup, kontakt_jmeno, kontakt_tel, kontakt_email, poznamka) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $statement->bind_param(
            'sssssisssssss',
            $values['firstName'], $values['lastName'], $values['birthDate'], $values['personalId'], $values['address'],
            $values['campId'], $values['roommate'], $values['arrival'], $values['departure'], $values['contactName'],
            $values['contactPhone'], $values['email'], $values['note']
        );
        if (! $statement->execute()) {
            $this->response->redirect('error', $statement->errno === 1062 ? 5 : 4);
        }

        $contactEmail = $season->contactEmail();
        wp_mail($email, $this->emailSubject($season), $this->emailMessage($season, $camp, $values), [
            "From: Táborová jízda <$contactEmail>",
            'Content-Type: text/plain; charset=UTF-8',
            "Cc: Táborová jízda <$contactEmail>",
        ]);
        $this->response->redirect('success', 1);
    }

    private function emailSubject(Season $season): string
    {
        $suffix = $season->isFull() ? ' (TÁBOR JIŽ OBSAZEN)' : '';
        return "Elektronická přihláška na tábor \"{$season->title()}\"$suffix";
    }

    private function emailMessage(Season $season, array $camp, array $values): string
    {
        $seasonData = $season->jsonSerialize();
        $price = (int) ($seasonData['price']['amount'] ?? $camp['cena']);
        $start = $season->startDate()?->format('j. n.') ?? date_create_from_format('!Y-m-d', $camp['termin_od'])->format('j. n.');
        $end = $season->endDate()?->format('j. n.') ?? date_create_from_format('!Y-m-d', $camp['termin_do'])->format('j. n.');
        $birthDate = date_create_from_format('!Y-m-d', $values['birthDate']);
        $fullText = $season->isFull()
            ? "Omlouváme se. TÁBOR JE JIŽ PLNĚ OBSAZEN!\r\nVaše přihláška bude zařazena do seznamu náhradníků, pokud se uvolní místo, ozveme se. TÁBOR ZATÍM NEPLAŤTE! \r\nDěkujeme za zájem i za pochopení.\r\n\r\n"
            : '';
        return "Dobrý den,\ntoto je automaticky vygenerovaný e-mail ze stránek https://taborovajizda.cz a byl Vám odeslán, protože Vaše adresa byla uvedena jako kontaktní e-mail v elektronické přihlášce na letní dětský tábor \"{$season->title()}\"\r\n\r\n" .
            $fullText .
            "Jméno dítěte: {$values['firstName']} {$values['lastName']}\r\nDatum narození: {$birthDate->format('j. n. Y')}\r\nRodné číslo: {$values['personalId']}\r\nAdresa bydliště: {$values['address']}\r\n" .
            'Kontakt jméno: ' . ($values['contactName'] ?? ' -') . "\r\nKontakt telefon: " . ($values['contactPhone'] ?? ' -') . "\r\nKontakt e-mail: {$values['email']}\r\n" .
            'S kým chci být ve stanu: ' . ($values['roommate'] ?? ' -') . "\r\nKde se k vám připojím: {$values['arrival']}\r\nZ tábora s Vámi pojedu: {$values['departure']}\r\n" .
            "Doplňující informace:\r\n" . ($values['note'] ?? '') . "\r\n\r\nTábor se koná v areálu Plátěná osada v termínu $start – $end {$season->year()}. Cena tábora je $price Kč. Za každého dalšího sourozence dítěte však zaplatíte pouze " . ($price - 500) . " Kč. " .
            "Platbu proveďte nejpozději do konce *května* na účet 27-3094190207/0100 a jako variabilní symbol uveďte rodné číslo svého dítěte. Pokud provádíte platbu pro více dětí najednou (sourozenci), použijte rodné číslo jednoho z nich.\r\n\r\n" .
            "Rádi Vám vystavíme fakturu pro zaměstnavatele, pokud chcete čerpat příspěvek z FKSP nebo potvrzení pro pojišťovnu o absolvování tábora.\r\n\r\n" .
            "V den odjezdu nezapomeňte potřebné dokumenty (potvrzení o zdravotní způsobilosti, potvrzení bezinfekčnosti, kartičku zdravotní pojišťovny, souhlas s využitím mediálních materiálů pro propagaci tábora, zmocnění pro zdravotníka)\r\n\r\n" .
            "Přejeme krásný den a těšíme se v srpnu na viděnou\r\nTým Táborové jízdy.";
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
}
