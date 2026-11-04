<?php

declare(strict_types=1);

namespace TaborovaJizda\Theme;

final class PostNotifications
{
    public function register(): void
    {
        add_action('transition_post_status', [$this, 'send'], 9, 3);
    }

    public function send(string $newStatus, string $oldStatus, \WP_Post $post): void
    {
        if ($newStatus !== 'publish' || $oldStatus === 'publish' || wp_get_environment_type() !== 'production') {
            return;
        }

        $isNews = array_filter(get_the_category($post->ID), static fn (\WP_Term $category): bool => $category->slug === 'aktuality');
        if ($isNews === []) {
            return;
        }

        try {
            $database = new ExternalDatabase();
            $result = $database->connection()->query('SELECT email, validate_id FROM newsletter WHERE email IS NOT NULL AND is_validated = TRUE');
            if (! $result) {
                return;
            }

            $subject = sprintf('www.taborovajizda.cz má nový příspěvek: %s', $post->post_title);
            $permalink = get_permalink($post->ID);
            $message = sprintf('Pokud se Vám příspěvek nezobrazuje správně, klikněte na odkaz: %s', $permalink) . "\n\n" . $post->post_content;
            $headers = ['From: Táborová jízda <info@taborovajizda.cz>', 'Content-Type: text/html; charset=UTF-8'];

            while ($row = $result->fetch_assoc()) {
                $unsubscribe = "\n\nPro odhlášení odběru novinek klikněte na následující odkaz: https://www.taborovajizda.cz/odeslat-dotaz/?unsubscribe={$row['validate_id']}";
                wp_mail($row['email'], $subject, $message . $unsubscribe, $headers);
            }
            wp_mail('info@taborovajizda.cz', $subject, $message, $headers);
        } catch (\Throwable $error) {
            error_log('[taborovajizda] Newsletter notification failed: ' . $error->getMessage());
        }
    }
}
