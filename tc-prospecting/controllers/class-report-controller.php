<?php if ( ! defined( 'ABSPATH' ) ) {exit;}

/**
 * Class ReportingController
 *
 * Provides AJAX endpoints for reporting: returning filter options (for select2)
 * and generating report data.
 */
class ReportingController {

    public static function init() {
        add_action('wp_ajax_get_report_options', [self::class, 'get_report_options']);
        add_action('wp_ajax_generate_report', [self::class, 'generate_report']);
    }

    /**
     * Returns options for reporting filters.
     * Expects a POST variable "filter_type" with values: system, sector, rm, or prospector.
     */
    public static function get_report_options() {
        global $wpdb;
        $filter_type = isset($_POST['filter_type']) ? sanitize_text_field($_POST['filter_type']) : '';
        $results = [];

        switch ($filter_type) {
            case 'system':
                $data = $wpdb->get_col("SELECT DISTINCT system FROM {$wpdb->prefix}tc_prospecting_planets ORDER BY system ASC");
                foreach ($data as $s) {
                    $results[] = ["id" => $s, "value" => $s];
                }
                break;
            case 'sector':
                $data = $wpdb->get_col("SELECT DISTINCT sector FROM {$wpdb->prefix}tc_prospecting_planets ORDER BY sector ASC");
                foreach ($data as $s) {
                    $results[] = ["id" => $s, "value" => $s];
                }
                break;
            case 'rm':
                $data = $wpdb->get_col("SELECT DISTINCT deposit_type FROM {$wpdb->prefix}tc_prospecting_deposits ORDER BY deposit_type ASC");
                foreach ($data as $s) {
                    $results[] = ["id" => $s, "value" => $s];
                }
                break;
            case 'prospector':
                $data = $wpdb->get_col("SELECT DISTINCT prospector FROM {$wpdb->prefix}tc_prospecting_deposits ORDER BY prospector ASC");
                foreach ($data as $s) {
                    $results[] = ["id" => $s, "value" => $s];
                }
                break;
        }
        wp_send_json_success($results);
    }

    /**
     * Generates a report based on filter type and value.
     */
    public static function generate_report() {
        global $wpdb;
        $filter_type  = isset($_POST['filter_type']) ? sanitize_text_field($_POST['filter_type']) : '';
        $filter_value = isset($_POST['filter_value']) ? sanitize_text_field($_POST['filter_value']) : '';
        $results = [];

        switch ( $filter_type ) {
            case 'system':
                $sql = "SELECT d.deposit_type, COUNT(*) as count, SUM(d.size) as total 
                        FROM {$wpdb->prefix}tc_prospecting_deposits d
                        INNER JOIN {$wpdb->prefix}tc_prospecting_planets p ON d.planet_id = p.id
                        WHERE p.system = %s
                        GROUP BY d.deposit_type";
                break;
        
            case 'sector':
                $sql = "SELECT d.deposit_type, COUNT(*) as count, SUM(d.size) as total 
                        FROM {$wpdb->prefix}tc_prospecting_deposits d
                        INNER JOIN {$wpdb->prefix}tc_prospecting_planets p ON d.planet_id = p.id
                        WHERE p.sector = %s
                        GROUP BY d.deposit_type";
                break;
        
            case 'rm':
                $sql = "SELECT p.name, p.system, p.sector, COUNT(*) as deposit_count, SUM(d.size) as total_amount
                        FROM {$wpdb->prefix}tc_prospecting_deposits d
                        INNER JOIN {$wpdb->prefix}tc_prospecting_planets p ON d.planet_id = p.id
                        WHERE d.deposit_type = %s
                        GROUP BY p.id
                        ORDER BY total_amount DESC";
                break;
        
            case 'prospector':
                $total_grids = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(DISTINCT CONCAT(planet_id, '_', x, '_', y))
                         FROM {$wpdb->prefix}tc_prospecting_deposits
                         WHERE prospector = %s",
                        $filter_value
                    )
                );
                $total_deposits = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) 
                         FROM {$wpdb->prefix}tc_prospecting_deposits
                         WHERE deposit_type != 'No deposit' 
                         AND prospector = %s",
                        $filter_value
                    )
                );
                $rm_breakdown = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT deposit_type, COUNT(*) as count, SUM(size) as total 
                         FROM {$wpdb->prefix}tc_prospecting_deposits
                         WHERE prospector = %s
                         GROUP BY deposit_type
                         ORDER BY total DESC",
                        $filter_value
                    ),
                    ARRAY_A
                );
                $results = [
                    'total_grids'    => $total_grids,
                    'total_deposits' => $total_deposits,
                    'rm_breakdown'   => $rm_breakdown,
                ];
                break;
        }
        
        if ( isset( $sql ) ) {
            $results = $wpdb->get_results(
                $wpdb->prepare( $sql, $filter_value ),
                ARRAY_A
            );
        }
        
        wp_send_json_success( [
            'type'         => $filter_type,
            'filter_value' => $filter_value,
            'data'         => $results,
        ] );
        
    }
}
ReportingController::init();
