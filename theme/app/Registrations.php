<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme;

use DateTimeImmutable;
use TaborovaJizda\Theme\Season\Season;

/**
 * Read-only access to the external registration database for the admin tools
 * (Tools → Seznam dětí, Tools → Kontakty rodičů).
 */
final class Registrations
{
    public const ALL = 'vse';

    private ExternalDatabase $database;

    public function __construct()
    {
        $this->database = new ExternalDatabase();
    }

    /** @return array<int, array{year: ?int, label: string, start: ?DateTimeImmutable}> Camp ID => season, newest first. */
    public function seasons(): array
    {
        $seasons = [];
        $result = $this->database->connection()->query('SELECT * FROM tabory ORDER BY id DESC');
        while ($result && ($row = $result->fetch_assoc())) {
            $start = date_create_immutable_from_format('!Y-m-d', (string) ($row['termin_od'] ?? '')) ?: null;
            $year = isset($row['rok']) && (int) $row['rok'] > 0 ? (int) $row['rok'] : ($start ? (int) $start->format('Y') : null);
            $title = trim((string) ($row['nazev'] ?? ''));
            if ($title === '' && $year === Season::current()->year()) {
                $title = Season::current()->title();
            }
            $label = $year === null ? 'Ročník ' . $row['id'] : (string) $year;
            if ($title !== '') {
                $label .= ' – ' . Home::dashes($title);
            }
            $seasons[(int) $row['id']] = ['year' => $year, 'label' => $label, 'start' => $start];
        }

        return $seasons;
    }

    /** The requested camp ID from `?tabor=`, null for all seasons; defaults to the active season. */
    public function selectedSeason(array $seasons, bool $allowAll): ?int
    {
        $requested = isset($_GET['tabor']) ? sanitize_key(wp_unslash((string) $_GET['tabor'])) : '';
        if ($allowAll && $requested === self::ALL) {
            return null;
        }
        if ($requested !== '' && isset($seasons[(int) $requested])) {
            return (int) $requested;
        }
        $active = Season::current()->externalSeasonKey();
        if ($active !== null && isset($seasons[(int) $active])) {
            return (int) $active;
        }

        return array_key_first($seasons);
    }

    /** @return list<array{email: string, name: string, children: string}> One row per parents' address; siblings are merged. */
    public function contacts(?int $campId): array
    {
        $where = $campId === null ? '' : 'WHERE id_tabora = ?';
        $statement = $this->database->connection()->prepare(
            "SELECT LOWER(TRIM(kontakt_email)) AS email, MAX(kontakt_jmeno) AS name,
                GROUP_CONCAT(DISTINCT CONCAT(jmeno, ' ', prijmeni) ORDER BY jmeno SEPARATOR ', ') AS children
            FROM prihlasky $where
            GROUP BY LOWER(TRIM(kontakt_email))
            ORDER BY email"
        );
        if ($campId !== null) {
            $statement->bind_param('i', $campId);
        }
        $statement->execute();

        $contacts = [];
        foreach ($statement->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            if (is_email((string) $row['email'])) {
                $contacts[] = ['email' => (string) $row['email'], 'name' => (string) ($row['name'] ?? ''), 'children' => (string) ($row['children'] ?? '')];
            }
        }

        return $contacts;
    }

    /**
     * Children registered for one camp, in registration order. The personal ID
     * and address stay in the database; only the derived gender leaves it.
     *
     * @return list<array<string, mixed>>
     */
    public function children(int $campId, ?DateTimeImmutable $campStart): array
    {
        $statement = $this->database->connection()->prepare(
            'SELECT jmeno, prijmeni, datum_nar, rc, spolubydlici, nastup, vystup, kontakt_jmeno, kontakt_tel, kontakt_email, poznamka
            FROM prihlasky WHERE id_tabora = ? ORDER BY id'
        );
        $statement->bind_param('i', $campId);
        $statement->execute();

        $at = $campStart ?? new DateTimeImmutable('today');
        $children = [];
        foreach ($statement->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
            $birth = date_create_immutable_from_format('!Y-m-d', (string) $row['datum_nar']) ?: null;
            $children[] = [
                'firstName' => (string) $row['jmeno'],
                'lastName' => (string) $row['prijmeni'],
                'birth' => $birth,
                'age' => self::ageAt($birth, $at),
                'gender' => self::gender((string) $row['rc']),
                'roommate' => (string) ($row['spolubydlici'] ?? ''),
                'arrival' => (string) ($row['nastup'] ?? ''),
                'departure' => (string) ($row['vystup'] ?? ''),
                'contactName' => (string) ($row['kontakt_jmeno'] ?? ''),
                'contactPhone' => self::phone((string) ($row['kontakt_tel'] ?? '')),
                'email' => (string) ($row['kontakt_email'] ?? ''),
                'note' => (string) ($row['poznamka'] ?? ''),
            ];
        }

        return $children;
    }

    /** Czech personal ID: girls have 50 (or 70 since 2004) added to the birth month. */
    public static function gender(string $personalId): ?string
    {
        $digits = preg_replace('/\D/', '', $personalId) ?? '';
        if (strlen($digits) < 9) {
            return null;
        }
        $month = (int) substr($digits, 2, 2);

        return match (true) {
            $month >= 1 && $month <= 12, $month >= 21 && $month <= 32 => 'kluk',
            $month >= 51 && $month <= 62, $month >= 71 && $month <= 82 => 'holka',
            default => null,
        };
    }

    /** Czech numbers as "777 111 222" (the form stores them without spaces, often with +420), which Excel keeps as text. */
    public static function phone(string $phone): string
    {
        $digits = preg_replace('/[\s\/-]/', '', $phone) ?? '';
        if (preg_match('/^(?:\+420|00420)?(\d{3})(\d{3})(\d{3})$/', $digits, $parts)) {
            return "$parts[1] $parts[2] $parts[3]";
        }

        return trim($phone);
    }

    /** Full years on the given day. */
    public static function ageAt(?DateTimeImmutable $birth, DateTimeImmutable $day): ?int
    {
        return $birth === null || $birth > $day ? null : $birth->diff($day)->y;
    }

    public static function renderSeasonPicker(string $page, array $seasons, ?int $selected, bool $allowAll): void
    {
        ?>
        <form method="get" style="margin:1rem 0">
            <input type="hidden" name="page" value="<?php echo esc_attr($page); ?>">
            <label for="tj-season">Ročník</label>
            <select name="tabor" id="tj-season">
                <?php foreach ($seasons as $id => $season) : ?>
                    <option value="<?php echo esc_attr((string) $id); ?>" <?php selected($selected, $id); ?>><?php echo esc_html($season['label']); ?></option>
                <?php endforeach; ?>
                <?php if ($allowAll) : ?>
                    <option value="<?php echo esc_attr(self::ALL); ?>" <?php selected($selected, null); ?>>Všechny ročníky</option>
                <?php endif; ?>
            </select>
            <button class="button">Zobrazit</button>
        </form>
        <?php
    }

    public static function unavailableNotice(): void
    {
        echo '<div class="notice notice-warning inline"><p>Databáze přihlášek není v tomto prostředí dostupná. Údaje jsou k dispozici v administraci webu, který má připojení k databázi (produkce, případně stage).</p></div>';
    }

    /** Sends a CSV download and ends the request: UTF-8 with BOM and semicolons, so Czech Excel opens it with the right encoding and columns. */
    public static function sendCsv(string $filename, array $header, array $rows): never
    {
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'wb');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, $header, ';');
        foreach ($rows as $row) {
            // Spreadsheet apps run cells that start with = + - @ as formulas.
            fputcsv($output, array_map(static fn ($value): string => preg_match('/^[=+\-@]/', (string) $value) ? "'" . $value : (string) $value, $row), ';');
        }
        fclose($output);
        exit;
    }

    public static function plural(int $count, string $one, string $few, string $many): string
    {
        return $count === 1 ? $one : ($count >= 2 && $count <= 4 ? $few : $many);
    }
}
