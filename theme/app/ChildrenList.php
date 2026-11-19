<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme;

/**
 * Tools → Seznam dětí: the children registered for one season with their age
 * on the first camp day and gender derived from the personal ID; every column
 * sorts in the browser. Read-only; administrators only.
 */
final class ChildrenList
{
    private const PAGE = 'tj-seznam-deti';
    private const CSV_ACTION = 'tj_children_csv';
    private const COLUMNS = [
        'number' => '#', 'lastName' => 'Příjmení', 'firstName' => 'Jméno', 'age' => 'Věk', 'gender' => 'Holka / kluk',
        'arrival' => 'Nástup', 'departure' => 'Výstup', 'roommate' => 'Ve stanu s', 'contactName' => 'Kontakt',
        'contactPhone' => 'Telefon', 'email' => 'E-mail', 'note' => 'Poznámka',
    ];

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addPage']);
        add_action('admin_post_' . self::CSV_ACTION, [$this, 'downloadCsv']);
    }

    /** The same columns as the table, in registration order. */
    public function downloadCsv(): never
    {
        if (! current_user_can('manage_options')) {
            wp_die('Nemáte oprávnění stáhnout seznam dětí.', '', ['response' => 403]);
        }
        check_admin_referer(self::CSV_ACTION);

        try {
            $registrations = new Registrations();
            $seasons = $registrations->seasons();
            $selected = $registrations->selectedSeason($seasons, false);
            $children = $selected === null ? [] : $registrations->children($selected, $seasons[$selected]['start']);
        } catch (\Throwable $error) {
            error_log('[taborovajizda] Children list unavailable: ' . $error->getMessage());
            wp_die('Databáze přihlášek není v tomto prostředí dostupná.');
        }

        $header = self::COLUMNS;
        $header['number'] = 'Číslo';
        $rows = [];
        foreach ($children as $index => $child) {
            $rows[] = [
                $index + 1, $child['lastName'], $child['firstName'], $child['age'] ?? '', $child['gender'] ?? '',
                $child['arrival'], $child['departure'], $child['roommate'], $child['contactName'],
                $child['contactPhone'], $child['email'], $child['note'],
            ];
        }
        $year = $selected === null ? 'rocnik' : (string) ($seasons[$selected]['year'] ?? $selected);
        Registrations::sendCsv("seznam-deti-$year.csv", array_values($header), $rows);
    }

    public function addPage(): void
    {
        add_management_page('Seznam dětí', 'Seznam dětí', 'manage_options', self::PAGE, [$this, 'render']);
    }

    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die('Nemáte oprávnění zobrazit seznam dětí.');
        }

        echo '<div class="wrap"><h1>Seznam dětí</h1>';
        try {
            $registrations = new Registrations();
            $seasons = $registrations->seasons();
            $selected = $registrations->selectedSeason($seasons, false);
            $start = $selected === null ? null : $seasons[$selected]['start'];
            $children = $selected === null ? [] : $registrations->children($selected, $start);
        } catch (\Throwable $error) {
            error_log('[taborovajizda] Children list unavailable: ' . $error->getMessage());
            Registrations::unavailableNotice();
            echo '</div>';
            return;
        }

        Registrations::renderSeasonPicker(self::PAGE, $seasons, $selected, false);
        if ($children === []) {
            echo '<p>Na tento ročník zatím nikdo není přihlášený.</p></div>';
            return;
        }

        $count = count($children);
        $girls = count(array_filter($children, static fn (array $child): bool => $child['gender'] === 'holka'));
        $boys = count(array_filter($children, static fn (array $child): bool => $child['gender'] === 'kluk'));
        $ages = array_filter(array_column($children, 'age'), 'is_int');
        $summary = [
            sprintf('%d %s', $count, Registrations::plural($count, 'dítě', 'děti', 'dětí')),
            sprintf('%d %s', $girls, Registrations::plural($girls, 'holka', 'holky', 'holek')),
            sprintf('%d %s', $boys, Registrations::plural($boys, 'kluk', 'kluci', 'kluků')),
        ];
        if ($ages !== []) {
            $summary[] = min($ages) === max($ages) ? sprintf('věk %d', min($ages)) : sprintf('věk %d–%d', min($ages), max($ages));
        }
        $columns = self::COLUMNS;
        $csvUrl = wp_nonce_url(add_query_arg(['action' => self::CSV_ACTION, 'tabor' => $selected], admin_url('admin-post.php')), self::CSV_ACTION);
        ?>
        <h2><?php echo esc_html(implode(' · ', $summary)); ?></h2>
        <p class="description">
            Věk je počítaný k prvnímu dni tábora<?php echo $start ? esc_html(' (' . $start->format('j. n. Y') . ')') : ''; ?>, pohlaví podle rodného čísla.
            Řadit jde kliknutím na název sloupce; výchozí pořadí je podle data přihlášení.
        </p>
        <p><a class="button" href="<?php echo esc_url($csvUrl); ?>">Stáhnout CSV</a> <span class="description">Stejné sloupce jako tabulka, v pořadí přihlášení; otevře se v Excelu.</span></p>
        <div style="overflow-x:auto">
            <table class="widefat striped" id="tj-children">
                <thead>
                    <tr>
                        <?php foreach ($columns as $key => $label) : ?>
                            <th scope="col"<?php echo $key === 'number' ? ' style="width:3rem"' : ''; ?>>
                                <?php if ($key === 'number') : ?>
                                    <?php echo esc_html($label); ?>
                                <?php else : ?>
                                    <button type="button" class="button-link tj-sort" data-type="<?php echo in_array($key, ['age'], true) ? 'number' : 'text'; ?>" style="font-weight:600;color:inherit;text-decoration:none"><?php echo esc_html($label); ?> <span aria-hidden="true"></span></button>
                                <?php endif; ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($children as $index => $child) : ?>
                        <tr>
                            <td data-sort="<?php echo esc_attr((string) $index); ?>"><?php echo esc_html((string) ($index + 1)); ?></td>
                            <td><strong><?php echo esc_html($child['lastName']); ?></strong></td>
                            <td><?php echo esc_html($child['firstName']); ?></td>
                            <?php // Sort by the birth date, so children of the same age keep their real order. ?>
                            <td data-sort="<?php echo esc_attr($child['birth'] ? (string) (-(int) $child['birth']->format('Ymd')) : ''); ?>" title="<?php echo esc_attr($child['birth'] ? 'Narozen(a) ' . $child['birth']->format('j. n. Y') : ''); ?>"><?php echo esc_html($child['age'] === null ? '–' : (string) $child['age']); ?></td>
                            <td data-sort="<?php echo esc_attr($child['gender'] ?? ''); ?>"><?php echo $child['gender'] === null ? '<span title="Nelze určit z rodného čísla">?</span>' : esc_html($child['gender']); ?></td>
                            <td><?php echo esc_html($child['arrival']); ?></td>
                            <td><?php echo esc_html($child['departure']); ?></td>
                            <td><?php echo esc_html($child['roommate']); ?></td>
                            <td><?php echo esc_html($child['contactName']); ?></td>
                            <td style="white-space:nowrap"><?php echo esc_html($child['contactPhone']); ?></td>
                            <td><?php echo esc_html($child['email']); ?></td>
                            <td><?php echo esc_html($child['note']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <script>
            (() => {
                const table = document.getElementById('tj-children');
                const body = table.tBodies[0];
                const headers = Array.from(table.tHead.rows[0].cells);
                const collator = new Intl.Collator('cs', { numeric: true, sensitivity: 'base' });
                const value = (row, column) => {
                    const cell = row.cells[column];
                    return cell.dataset.sort ?? cell.textContent.trim();
                };
                headers.forEach((header, column) => {
                    const button = header.querySelector('.tj-sort');
                    if (!button) return;
                    button.addEventListener('click', () => {
                        const ascending = header.getAttribute('aria-sort') !== 'ascending';
                        const numeric = button.dataset.type === 'number';
                        const rows = Array.from(body.rows);
                        rows.sort((a, b) => {
                            const x = value(a, column);
                            const y = value(b, column);
                            // Empty cells always go last.
                            if (x === '' || y === '') return x === y ? 0 : (x === '' ? 1 : -1);
                            const order = numeric ? Number(x) - Number(y) : collator.compare(x, y);
                            return (ascending ? order : -order) || Number(a.cells[0].dataset.sort) - Number(b.cells[0].dataset.sort);
                        });
                        rows.forEach((row, index) => {
                            row.cells[0].textContent = String(index + 1);
                            body.append(row);
                        });
                        headers.forEach((other) => {
                            other.removeAttribute('aria-sort');
                            const arrow = other.querySelector('.tj-sort span');
                            if (arrow) arrow.textContent = '';
                        });
                        header.setAttribute('aria-sort', ascending ? 'ascending' : 'descending');
                        button.querySelector('span').textContent = ascending ? '▲' : '▼';
                    });
                });
            })();
        </script>
        </div>
        <?php
    }
}
