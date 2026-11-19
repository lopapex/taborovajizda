<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme;

/**
 * Tools → Kontakty rodičů: the parents' e-mail addresses of one season (or all
 * of them) from the external registration database, to copy into Bcc or to
 * download as CSV for a mailing service. Read-only; administrators only.
 */
final class ParentContacts
{
    private const PAGE = 'tj-kontakty-rodicu';
    private const CSV_ACTION = 'tj_parent_contacts_csv';

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
            $registrations = new Registrations();
            $seasons = $registrations->seasons();
            $selected = $registrations->selectedSeason($seasons, true);
            $contacts = $registrations->contacts($selected);
        } catch (\Throwable $error) {
            error_log('[taborovajizda] Parent contacts unavailable: ' . $error->getMessage());
            Registrations::unavailableNotice();
            echo '</div>';
            return;
        }

        Registrations::renderSeasonPicker(self::PAGE, $seasons, $selected, true);
        if ($contacts === []) {
            echo '<p>Na tento ročník zatím nikdo není přihlášený.</p></div>';
            return;
        }

        $emails = implode(', ', array_column($contacts, 'email'));
        $count = count($contacts);
        $csvUrl = wp_nonce_url(add_query_arg(['action' => self::CSV_ACTION, 'tabor' => $selected ?? Registrations::ALL], admin_url('admin-post.php')), self::CSV_ACTION);
        ?>
        <div class="notice notice-info inline"><p><strong>Adresy vložte do pole Skrytá kopie (Bcc)</strong>, nikdy do Komu ani Kopie. Jinak rodiče uvidí adresy ostatních.</p></div>
        <h2><?php echo esc_html(sprintf('%d %s', $count, Registrations::plural($count, 'adresa', 'adresy', 'adres'))); ?></h2>
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
            $registrations = new Registrations();
            $seasons = $registrations->seasons();
            $selected = $registrations->selectedSeason($seasons, true);
            $contacts = $registrations->contacts($selected);
        } catch (\Throwable $error) {
            error_log('[taborovajizda] Parent contacts unavailable: ' . $error->getMessage());
            wp_die('Databáze přihlášek není v tomto prostředí dostupná.');
        }

        $year = $selected === null ? 'vsechny-rocniky' : (string) ($seasons[$selected]['year'] ?? $selected);
        Registrations::sendCsv("kontakty-rodicu-$year.csv", ['email', 'jmeno', 'deti'], array_map(
            static fn (array $contact): array => [$contact['email'], $contact['name'], $contact['children']],
            $contacts
        ));
    }
}
