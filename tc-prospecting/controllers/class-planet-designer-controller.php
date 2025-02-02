<?php if (!defined('ABSPATH')) {exit;}

/**
 * Class PlanetDesignerController
 *
 * Manages the AJAX updates for:
 *  - Updating planet info (e.g. name, system, sector, etc.)
 *  - Changing grid terrain cells
 *  - Bulk saving any terrain changes
 */
class PlanetDesignerController {

    public static function init() {
        add_action('wp_ajax_update_planet_info', [self::class, 'updatePlanetInfo']);
        add_action('wp_ajax_save_terrain_cell', [self::class, 'saveTerrainCell']);
        add_action('wp_ajax_save_planet_terrain_bulk', [self::class, 'savePlanetTerrainBulk']);
    }

    /**
     * Updates a planet’s high-level info via AJAX (Name, System, Sector, etc.).
     * Called by "update_planet_info" action from planet-designer.js.
     */
    public static function updatePlanetInfo() {
        check_ajax_referer('SecurityNonce', 'security');

        $planet_id  = intval($_POST['planet_id'] ?? 0);
        $planetName = sanitize_text_field($_POST['planet_name'] ?? '');
        $system     = sanitize_text_field($_POST['system'] ?? '');
        $sector     = sanitize_text_field($_POST['sector'] ?? '');
        $location   = sanitize_text_field($_POST['location'] ?? '');
        $size       = intval($_POST['size'] ?? 0);

        if (!$planet_id || !$planetName || !$system || !$sector || !$location || !$size) {
            wp_send_json_error(['message' => 'Invalid data provided.']);
        }

        $planet = Planet::load($planet_id);
        if (!$planet) {
            wp_send_json_error(['message' => 'Planet not found.']);
        }

        $planet->setName($planetName);
        $planet->setSystem($system);
        $planet->setSector($sector);
        $planet->setLocation($location);
        $planet->setSize($size);

        $res = $planet->saveToDatabase();
        if ($res === false) {
            wp_send_json_error(['message' => 'Failed to update planet info.']);
        }

        wp_send_json_success(['message' => 'Planet updated successfully.']);
    }

    /**
     * Save or update a single grid cell’s terrain (e.g., user picks a new terrain code).
     * Called by "save_terrain_cell" action from planet-designer.js.
     */
    public static function saveTerrainCell() {
        check_ajax_referer('SecurityNonce', 'security');

        $planet_id    = intval($_POST['planet_id'] ?? 0);
        $x            = intval($_POST['x'] ?? -1);
        $y            = intval($_POST['y'] ?? -1);
        $terrain_code = sanitize_text_field($_POST['terrain_code'] ?? 'unknown');

        if ($planet_id <= 0 || $x < 0 || $y < 0) {
            wp_send_json_error(['message' => 'Invalid coordinates or planet ID.']);
        }

        $planet = Planet::load($planet_id);
        if (!$planet) {
            wp_send_json_error(['message' => 'Planet not found.']);
        }

        // Attempt to find the matching Grid or create one if missing
        $matchingGrid = null;
        foreach ($planet->getGrids() as $grid) {
            if ($grid->getX() === $x && $grid->getY() === $y) {
                $matchingGrid = $grid;
                break;
            }
        }
        if (!$matchingGrid) {
            $matchingGrid = $planet->addGrid($x, $y, $terrain_code); 
        } else {
            $matchingGrid->setTerrainType($terrain_code);
            $matchingGrid->saveToDatabase(); 
        }

        wp_send_json_success(['message' => 'Terrain updated.']);
    }

    /**
     * Method to handle a “bulk save” of terrain data for the entire planet.
     * The front-end can pass a JSON array of terrain changes, which we apply.
     * Called by "save_planet_terrain_bulk" in planet-designer.js.
     *
     */
    public static function savePlanetTerrainBulk() {
        check_ajax_referer('SecurityNonce', 'security');
    
        $planet_id = intval($_POST['planet_id'] ?? 0);
        $terrain_data = isset($_POST['terrain_data'])
            ? json_decode(wp_unslash($_POST['terrain_data']), true)
            : [];
    
        if ($planet_id <= 0 || !is_array($terrain_data)) {
            wp_send_json_error(['message' => 'Invalid planet or terrain data.']);
        }
        
        if (empty($terrain_data)) {
            wp_send_json_success(['message' => 'No terrain changes to save.']);
        }
    
        $planet = Planet::load($planet_id);
        if (!$planet) {
            wp_send_json_error(['message' => 'Planet not found.']);
        }
    
        global $wpdb;
        $wpdb->query('START TRANSACTION');
    
        try {
            foreach ($terrain_data as $cell) {
                $x = (int)($cell['x'] ?? -1);
                $y = (int)($cell['y'] ?? -1);
                $code = sanitize_text_field($cell['terrain'] ?? 'unknown');
                if ($x < 0 || $y < 0) {
                    throw new Exception("Invalid coordinate data: x=$x, y=$y");
                }
    
                // Scan through the planet's grids for a match
                $foundGrid = null;
                foreach ($planet->getGrids() as $g) {
                    if ($g->getX() === $x && $g->getY() === $y) {
                        $foundGrid = $g;
                        break;
                    }
                }
                if (!$foundGrid) {
                    $foundGrid = $planet->addGrid($x, $y, $code); 
                } else {
                    $foundGrid->setTerrainType($code);
                    $foundGrid->saveToDatabase(); 
                }
            }
            $wpdb->query('COMMIT');
            wp_send_json_success(['message' => 'All terrain changes saved.']);
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }    
}
PlanetDesignerController::init();