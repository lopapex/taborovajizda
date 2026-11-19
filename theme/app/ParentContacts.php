<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme;

use TaborovaJizda\Theme\Season\Season;
use TaborovaJizda\Theme\Season\SeasonArchive;

/**
 * Tools → Kontakty rodičů: the parents' e-mail addresses of one season (or all
 * of them) from the external registration database, to copy into Bcc or to
 * download as CSV for a mailing service. Read-only; administrators only.
 */
final class ParentContacts
{
    private const PAGE = 'tj-kontakty-rodicu';
    private const CSV_ACTION = 'tj_parent_contacts_csv';
    private const ALL = 'vse';

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addPage']);
        add_action('admin_post_' . self::CSV_ACTION, [$this, 'downloadCsv']);
    }

    public function addPage(): void
    {
        add_management_page('Kontakty rodičů', 'Kontakty rodičů', 'manage_options', self::PAGE, [$this, 'render']);
    }

    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die('Nemáte oprávnění zobrazit kontakty rodičů.');
        }

        echo '<div class="wrap"><h1>Kontakty rodičů</h1>';
        try {
            $database = new ExternalDatabase();
            $seasons = $this->seasons($database->connection());
            $selected = $this->selectedSeason($seasons);
            $contacts = $this->contacts($database->connection(), $selected);
        } catch (\Throwable $error) {
            error_log('[taborovajizda] Parent contacts unavailable: ' . $error->getMessage());
            echo '<div class="notice notice-warning inline"><p>Databáze přihlášek není v tomto prostředí dostupná. Kontakty jsou k dispozici v administraci produkčního webu.</p></div></div>';
            return;
        }

        $this->renderPicker($seasons, $selected);
        if ($contacts === []) {
            echo '<p>Na tento ročník zatím nikdo není přihlášený.</p></div>';
            return;
        }

        $emails = implode(', ', array_column($contacts, 'email'));
        $count = count($contacts);
        $csvUrl = wp_nonce_url(add_query_arg(['action' => self::CSV_ACTION, 'tabor' => $selected ?? self::ALL], admin_url('admin-post.php')), self::CSV_ACTION);
        ?>
        <div class="notice notice-info inline"><p><strong>Adresy vložte do pole Skrytá kopie (Bcc)</strong>, nikdy do Komu ani Kopie. Jinak rodiče uvidí adresy ostatních.</p></div>
        <h2><?php echo esc_html(sprintf('%d %s', $count, $this->plural($count, 'adresa', 'adresy', 'adres'))); ?></h2>
        <p>Sourozenci se stejným e-mailem jsou sloučení do jedné adresy.</p>
        <textarea id="tj-parent-emails" class="large-text code" rows="5" readonly><?php echo esc_textarea($emails); ?></textarea>
        <p>
            <button type="button" class="button button-primary" id="tj-copy-emails">Kopírovat adresy</button>
            <a class="button" href="<?php echo esc_url($csvUrl); ?>">Stáhnout CSV</a>
            <span class="description">CSV (e-mail, jméno kontaktu, děti) se hodí pro import do rozesílací služby.</span>
        </p>
        <table class="widefat striped" style="max-width:60rem">
            <thead><tr><th>E-mail</th><th>Kontaktní osoba</th><th>Děti</th></tr></thead>
            <tbody>
                <?php foreach ($contacts as $contact) : ?>
                    <tr><td><?php echo esc_html($contact['email']); ?></td><td><?php echo esc_html($contact['name']); ?></td><td><?php echo esc_html($contact['children']); ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <script>
            document.getElementById('tj-copy-emails').addEventListener('click', async (event) => {
                const button = event.currentTarget;
                const field = document.getElementById('tj-parent-emails');
                try {
                    await navigator.clipboard.writeText(field.value);
                } catch {
                    field.select();
                    document.execCommand('copy');
                }
                button.textContent = 'Zkopírováno';
                setTimeout(() => { button.textContent = 'Kopírovat adresy'; }, 2000);
            });
        </script>
        </div>
        <?php
    }

    public function downloadCsv(): never
    {
        if (! current_user_can('manage_options')) {
            wp_die('Nemáte oprávnění stáhnout kontakty rodičů.', '', ['response' => 403]);
        }
        check_admin_referer(self::CSV_ACTION);

        try {
            $database = new ExternalDatabase();
            $seasons = $this->seasons($database->connection());
            $selected = $this->selectedSeason($seasons);
            $contacts = $this->contacts($database->connection(), $selected);
        } catch (\Throwable $error) {
            error_log('[taborovajizda] Parent contacts unavailable: ' . $error->getMessage());
            wp_die('Databáze přihlášek není v tomto prostředí dostupná.');
        }

        $year = $selected === null ? 'vsechny-rocniky' : (string) ($seasons[$selected]['year'] ?? $selected);
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="kontakty-rodicu-' . $year . '.csv"');
        $output = fopen('php://output', 'wb');
        // BOM and semicolons: Czech Excel opens the file with the right encoding and columns.
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['email', 'jmeno', 'deti'], ';');
        foreach ($contacts as $contact) {
            fputcsv($output, array_map([$this, 'safeCell'], [$contact['email'], $contact['name'], $contact['children']]), ';');
        }
        fclose($output);
        exit;
    }

    /** @return array<int, array{year: ?int, label: string}> Camp ID => label, newest first. */
    private function seasons(\mysqli $connection): array
    {
        $titles = [Season::current()->year() => Season::current()->title()];
        foreach (SeasonArchive::all() as $season) {
            $titles[$season['year']] ??= $season['title'];
        }

        $seasons = [];
        $result = $connection->query('SELECT * FROM tabory ORDER BY id DESC');
        while ($result && ($row = $result->fetch_assoc())) {
            $start = date_create_from_format('!Y-m-d', (string) ($row['termin_od'] ?? ''));
            $year = $start ? (int) $start->format('Y') : null;
            $label = $year === null ? 'Ročník' : (string) $year;
            if ($year !== null && isset($titles[$year])) {
                $label .= ' – ' . Home::dashes($titles[$year]);
            }
            $seasons[(int) $row['id']] = ['year' => $year, 'label' => $label];
        }

        return $seasons;
    }

    /** The requested camp ID, null for all seasons; defaults to the active season. */
    private function selectedSeason(array $seasons): ?int
    {
        $requested = isset($_GET['tabor']) ? sanitize_key(wp_unslash((string) $_GET['tabor'])) : '';
        if ($requested === self::ALL) {
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

    /** @return list<array{email: string, name: string, children: string}> One row per address. */
    private function contacts(\mysqli $connection, ?int $campId): array
    {
        $where = $campId === null ? '' : 'WHERE id_tabora = ?';
        $statement = $connection->prepare(
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

    private function renderPicker(array $seasons, ?int $selected): void
    {
        ?>
        <form method="get" style="margin:1rem 0">
            <input type="hidden" name="page" value="<?php echo esc_attr(self::PAGE); ?>">
            <label for="tj-season">Ročník</label>
            <select name="tabor" id="tj-season">
                <?php foreach ($seasons as $id => $season) : ?>
                    <option value="<?php echo esc_attr((string) $id); ?>" <?php selected($selected, $id); ?>><?php echo esc_html($season['label']); ?></option>
                <?php endforeach; ?>
                <option value="<?php echo esc_attr(self::ALL); ?>" <?php selected($selected, null); ?>>Všechny ročníky</option>
            </select>
            <button class="button">Zobrazit</button>
        </form>
        <?php
    }

    /** Spreadsheet apps run cells that start with = + - @ as formulas. */
    private function safeCell(string $value): string
    {
        return preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;
    }

    private function plural(int $count, string $one, string $few, string $many): string
    {
        return $count === 1 ? $one : ($count >= 2 && $count <= 4 ? $few : $many);
    }
}
