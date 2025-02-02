<?php if (!defined('ABSPATH')) {exit;}

/**
 * Class PlanetImportController
 *
 * Allows users to select a planet from an external DB and import it into WP.
 */
class PlanetImportController {

    public static function init() {
        add_action('wp_ajax_add_selected_planet', [self::class, 'addSelectedPlanet']);
    }

    public static function addPlanet() {
        require_once __DIR__ . '/../views/planet-import-view.php';
        render_planet_import_view();
    }

    /**
     * Ajax handler that fetches planet info from the external DB and creates a Planet.
     */
    public static function addSelectedPlanet() {
        check_ajax_referer('SecurityNonce', 'nonce');
        global $wpdb;
        $debugInfo  = [];
        $planet_uid = sanitize_text_field($_POST['planet_uid'] ?? '');
        if (!$planet_uid) {
            wp_send_json_error(['message' => 'No planet UID provided']);
        }
        try {
            $row = $wpdb->get_row($wpdb->prepare(
                "SELECT p.name AS planet_name,
                        s.name AS system_name,
                        sec.name AS sector_name,
                        ST_X(p.coordinates) AS coordX,
                        ST_Y(p.coordinates) AS coordY,
                        p.size,
                        p.terrain_map
                 FROM swc_planets p
                 JOIN swc_systems s ON p.system = s.uid
                 JOIN swc_sectors sec ON s.sector = sec.uid
                 WHERE p.uid = %s",
                $planet_uid
            ));
            if (!$row) {
                throw new Exception('Planet not found in external DB.');
            }
            $debugInfo['external_row'] = $row;
            $coords = "{$row->coordX},{$row->coordY}";

            $newPlanet = new Planet(
                0,
                (string)$row->planet_name,
                (string)$row->system_name,
                (string)$row->sector_name,
                (string)$coords,
                (int)$row->size
            );
            // 4) Save new Planet.
            if (!$newPlanet->saveToDatabase()) {
                throw new Exception('Failed to save Planet in WP DB.');
            }
            $debugInfo['planet_id'] = $newPlanet->getId();

            // Build grids from terrain map.
            $terrainMap = $row->terrain_map ?? '';
            $gridErrors = 0;
            if (!empty($terrainMap)) {
                $sizeVal = (int)$row->size;
                $cells   = str_split($terrainMap);
                for ($y = 0; $y < $sizeVal; $y++) {
                    for ($x = 0; $x < $sizeVal; $x++) {
                        $i = $y * $sizeVal + $x;
                        $t = isset($cells[$i]) ? $cells[$i] : 'unknown';
                        $g = $newPlanet->addGrid($x, $y, $t);
                        if (!$g->getId()) {
                            $gridErrors++;
                            $debugInfo['grid_errors'][] = [
                                'x' => $x,
                                'y' => $y,
                                'terrain' => $t,
                                'reason' => 'No ID after saving grid'
                            ];
                        }
                    }
                }
                if ($gridErrors > 0) {
                    throw new Exception("Failed to create $gridErrors grid entries.");
                }
            }
            wp_send_json_success([
                'message' => "Planet imported successfully with {$row->size}x{$row->size} grids.",
                'debug'   => $debugInfo
            ]);
        } catch (Exception $e) {
            wp_send_json_error([
                'message'  => $e->getMessage(),
                'debug'    => $debugInfo,
                'db_error' => $wpdb->last_error
            ]);
        }
    }
}
PlanetImportController::init();