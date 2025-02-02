<?php if (!defined('ABSPATH')) {exit;}

class ChangelogView {

    public static function init() {
        add_action('wp_ajax_fetch_planet_changelog', [self::class, 'fetchPlanetChangelog']);
        add_action('wp_ajax_fetch_grid_changelog_summary', [self::class, 'fetchGridChangelogSummary']);
        add_action('wp_ajax_fetch_grid_changelog_all', [self::class, 'fetchGridChangelogAll']);
    }

    /**
     * Returns the changelog for a given planet.
     * Expects: POST 'planet_id' and 'security' (nonce).
     */
    public static function fetchPlanetChangelog() {
        check_ajax_referer('SecurityNonce', 'security');

        if (empty($_POST['planet_id'])) {
            wp_send_json_error(['message' => 'Planet ID is missing.']);
        }
        $planet_id = intval($_POST['planet_id']);
        global $wpdb;
        $table = $wpdb->prefix . 'tc_prospecting_changelog';

        $logs = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE planet_id = %d ORDER BY datetime DESC",  $planet_id),
            ARRAY_A
        );

        wp_send_json_success(['changelog' => $logs]);
    }


    /**
     * Fetches the latest three changelog entries for a deposit/grid.
     *
     * Expects POST 'target_id' and 'security' (nonce).
     * Returns only the latest three changes.
     */
    public static function fetchGridChangelogSummary() {
        check_ajax_referer('SecurityNonce', 'security');

        if (empty($_POST['target_id'])) {
            wp_send_json_error(['message' => 'Target ID is missing.']);
        }
        $target_id = intval($_POST['target_id']);
        global $wpdb;
        $table = $wpdb->prefix . 'tc_prospecting_changelog';
        
        $logs = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE object_type = %s AND object_id = %d ORDER BY datetime DESC LIMIT 3", 'deposit', $target_id),
            ARRAY_A
        );

        wp_send_json_success(['changelog' => $logs]);
    }

    /**
     * Fetches all changelog entries for a deposit.
     *
     * Expects 'deposit_id'
     */
    public static function fetchGridChangelogAll($deposit_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'tc_prospecting_changelog';
        
        $logs = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE object_type = %s AND object_id = %d ORDER BY datetime DESC", 'deposit', $deposit_id),
            ARRAY_A
        );
        return $logs;
    }
}

// Initialize the endpoints.
ChangelogView::init();
