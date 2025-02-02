<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class DepositsController
 *
 * Handles saving, updating, and deleting deposits (resources) via AJAX.
 */
class DepositsController {

    public static function init() {
        add_action('wp_ajax_save_resource', [self::class, 'save_resource']);
        add_action('wp_ajax_update_deposit', [self::class, 'update_deposit']);
        add_action('wp_ajax_delete_deposit', [self::class, 'delete_deposit']);
        
    }

    /**
     * AJAX handler to save a new deposit.
     * Expects POST fields:
     *  - planet_id, x, y, resource, amount, prospecting_time, prospector, prospector_skill, prospector_vehicle
     */
    public static function save_resource() {
        check_ajax_referer('SecurityNonce', 'security');
    
        $planet_id        = intval($_POST['planet_id'] ?? 0);
        $x                = intval($_POST['x'] ?? -1);
        $y                = intval($_POST['y'] ?? -1);
        $deposit_type     = sanitize_text_field($_POST['resource'] ?? '');
        $size             = intval($_POST['amount'] ?? 0);
        $prospecting_time = sanitize_text_field($_POST['prospecting_time'] ?? '');
        $prospector       = sanitize_text_field($_POST['prospector'] ?? '');
        $prospector_skill = intval($_POST['prospector_skill'] ?? 5);
        $prospector_vehicle = sanitize_text_field($_POST['prospector_vehicle'] ?? 'SX-65 Groundhog');
    
        if (!$planet_id || $x < 0 || $y < 0) {
            wp_send_json_error(['message' => 'Invalid planet ID or coordinates.']);
        }
        
        $planet = Planet::load($planet_id);
        if (!$planet) {
            wp_send_json_error(['message' => 'Planet not found.']);
        }
        
        $existingDeposits = Deposit::getDepositsByGrid($planet_id, $x, $y);
        if (!empty($existingDeposits)) {
            wp_send_json_error(['message' => 'Deposit already exists.']);
        } else {
            try {
                $dt = (!empty($prospecting_time)) ? new DateTime($prospecting_time) : null;
            } catch (Exception $e) {
                $dt = null;
            }
            $deposit = new Deposit(
                0, 
                $planet_id,
                $x,
                $y,
                $deposit_type,
                $size,
                $dt,
                $prospector,
                null, 
                null, 
                $prospector_skill,
                $prospector_vehicle
            );
            $deposit->saveToDatabase();
        }
        
        wp_send_json_success(['message' => 'Deposit saved successfully.']);
    }
    

    /**
     * AJAX handler to update an existing deposit.
     * Expects POST fields:
     *   - deposit_id, resource, amount, prospecting_time, prospector, prospector_skill, prospector_vehicle
     */
    public static function update_deposit() {
        check_ajax_referer('SecurityNonce', 'security');

        $deposit_id       = intval($_POST['deposit_id'] ?? 0);
        $resource         = sanitize_text_field($_POST['resource'] ?? '');
        $amount           = intval($_POST['amount'] ?? 0);
        $prospector       = sanitize_text_field($_POST['prospector'] ?? '');
        $prospecting_time = sanitize_text_field($_POST['prospecting_time'] ?? '');
        $prospector_skill = intval($_POST['prospector_skill'] ?? 5);
        $prospector_vehicle = sanitize_text_field($_POST['prospector_vehicle'] ?? 'SX-65 Groundhog');

        if (!$deposit_id) {
            wp_send_json_error(['message' => 'Invalid deposit ID.']);
        }
        $deposit = new Deposit($deposit_id);
        if (!$deposit->loadFromDatabase()) {
            wp_send_json_error(['message' => 'Deposit not found.']);
        }

        $deposit->setDepositType($resource);
        $deposit->updateSize($amount);
        $deposit->setProspector($prospector);
        $deposit->setProspectorSkill($prospector_skill);
        $deposit->setProspectorVehicle($prospector_vehicle);
        if (!empty($prospecting_time)) {
            try {
                $dt = new DateTime($prospecting_time);
                $deposit->setProspectingTime($dt);
            } catch (Exception $e) {
                // leave as is on error
            }
        }
        $current_user = wp_get_current_user();
        $deposit->setLastUpdater($current_user->user_login ?: 'Unknown');
        $deposit->saveToDatabase();

        wp_send_json_success(['message' => 'Deposit updated successfully.']);
    }

    /**
     * AJAX handler to delete a deposit.
     * Expects POST field: deposit_id
     */
    public static function delete_deposit() {
        check_ajax_referer('SecurityNonce', 'security');

        $deposit_id = intval($_POST['deposit_id'] ?? 0);
        if (!$deposit_id) {
            wp_send_json_error(['message' => 'No valid deposit ID provided.']);
        }
        $deposit = new Deposit($deposit_id);
        if (!$deposit->loadFromDatabase()) {
            wp_send_json_error(['message' => 'Deposit not found.']);
        }
        if (!$deposit->delete()) {
            wp_send_json_error(['message' => 'Failed to delete deposit.']);
        }
        wp_send_json_success(['message' => 'Deposit deleted successfully.']);
    }
}
DepositsController::init();