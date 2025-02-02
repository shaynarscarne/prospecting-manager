<?php if (!defined('ABSPATH')) exit;

/**
 * planet-designer-view.php
 * 
 * Renders the UI for designing a planet:
 *  - Terrain grid
 *  - Editing planet info
 *  - Button to save changes
 *
 * Relies on planet-designer.js for the event handlers and AJAX logic.
 */

function render_planet_designer_view(Planet $planet) {
    $size   = $planet->getSize();
    $grids  = $planet->getGrids();

    $terrainMap = [];
    foreach ($grids as $grid) {
        $terrainMap["{$grid->getX()}_{$grid->getY()}"] = $grid->getTerrainType();
    }
    ?>

    <div class="wrap">

        <!-- Navigation / Actions -->
        <div style="margin-bottom: 20px;">
            <a href="?" class="button button-secondary">
                <span class="dashicons dashicons-arrow-left-alt"></span>
                Go Back
            </a>
            <button id="savePlanetBtn" class="button button-primary" style="margin-left: 10px;">
                <span class="dashicons dashicons-saved"></span>
                Save Planet Terrain
            </button>
            <button id="modifyPlanetBtn" class="button" style="margin-left: 10px;">
                Modify Planet
            </button>
        </div>

        <h2>Planet Designer - <?php echo esc_html($planet->getName()); ?></h2>

        <!-- Planet Info -->
        <div class="planet-info">
            <h3>Planet Info</h3>
            <table class="form-table">
                <tr><th>Name:</th><td><?php echo esc_html($planet->getName()); ?></td></tr>
                <tr><th>System:</th><td><?php echo esc_html($planet->getSystem()); ?></td></tr>
                <tr><th>Sector:</th><td><?php echo esc_html($planet->getSector()); ?></td></tr>
                <tr><th>Location:</th><td><?php echo esc_html($planet->getLocation()); ?></td></tr>
                <tr><th>Size:</th><td><?php echo esc_html($planet->getSize()); ?></td></tr>
            </table>
        </div>

        <!-- Terrain Grid -->
        <div class="terrain-grid">
            <h3>Terrain Grid (<?php echo $size; ?>x<?php echo $size; ?>)</h3>
            <table class="terrain-table">
                <thead>
                    <tr>
                        <th style="text-align:center;"></th>
                        <?php for($x=0; $x<$size; $x++): ?>
                            <th style="text-align:center;"><?php echo $x; ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                <?php for($y=0; $y<$size; $y++): ?>
                    <tr>
                        <th class='terrainTH'><?php echo $y; ?></th>
                        <?php for($x=0; $x<$size; $x++):
                            $key = "{$x}_{$y}";
                            $terrainCode = $terrainMap[$key] ?? '';
                            $terrainImg  = $terrainCode
                                ? "<img src='https://images.swcombine.com/galaxy/terrains/{$terrainCode}/terrain.gif' height='60' width='60' alt='".esc_attr($terrainCode)."'>"
                                : '·';
                            ?>
                            <td class='terrainTD'
                                data-x="<?php echo $x; ?>"
                                data-y="<?php echo $y; ?>"
                                onclick="openTerrainModal(<?php echo $x; ?>, <?php echo $y; ?>)">
                                <?php echo $terrainImg; ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modify Planet Modal -->
    <div id="modifyPlanetModal" class="modal">
        <div class="modal-content">
            <h2>Modify Planet Info</h2>
            <form id="modifyPlanetForm" method="POST">
            <input type="hidden" name="planet_id" id="modifyPlanetId" value="<?php echo $planet->getId(); ?>">
            <table class="form-table">
                    <tr>
                        <th><label for="modifyPlanetName">Name</label></th>
                        <td>
                            <input type="text" id="modifyPlanetName" name="planet_name"
                                   class="regular-text"
                                   value="<?php echo esc_attr($planet->getName()); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="modifySystem">System</label></th>
                        <td>
                            <input type="text" id="modifySystem" name="system"
                                   class="regular-text"
                                   value="<?php echo esc_attr($planet->getSystem()); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="modifySector">Sector</label></th>
                        <td>
                            <input type="text" id="modifySector" name="sector"
                                   class="regular-text"
                                   value="<?php echo esc_attr($planet->getSector()); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="modifyLocation">Location</label></th>
                        <td>
                            <input type="text" id="modifyLocation" name="location"
                                   class="regular-text"
                                   value="<?php echo esc_attr($planet->getLocation()); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="modifySize">Size</label></th>
                        <td>
                            <input type="number" id="modifySize" name="size"
                                   class="regular-text"
                                   value="<?php echo intval($planet->getSize()); ?>"
                                   min="1" max="100"
                                   required>
                        </td>
                    </tr>
                </table>
                <div style="margin-top:10px;">
                    <button id="savePlanetInfoBtn" type="button" class="button button-primary">Save Planet Info</button>
                    <button id="cancelModifyPlanetBtn" type="button" class="button button-secondary" style="margin-left:10px;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Terrain Selection Modal -->
<!-- Terrain Selection Modal -->
<div id="terrainModal" class="modal">
    <div class="modal-content">
        <h3>Select Terrain Type</h3>
        <div class="terrain-grid">
            <?php
            $terrains = [
                'Cave' => 'n', 'Crater' => 'i', 'Desert' => 'b', 'Forest' => 'c',
                'Gas Giant' => 'o', 'Glacier' => 'k', 'Grassland' => 'f', 'Jungle' => 'd',
                'Mountain' => 'l', 'Ocean' => 'g', 'River' => 'h', 'Rock' => 'j',
                'Swamp' => 'e', 'Volcanic' => 'm'
            ];
            foreach ($terrains as $tName => $tCode): ?>
                <div class="terrain-option" onclick="selectTerrain('<?php echo esc_js($tCode); ?>')">
                    <img src="https://images.swcombine.com/galaxy/terrains/<?php echo esc_attr($tCode); ?>/terrain.gif"
                         alt="<?php echo esc_attr($tName); ?>" height="32" width="32">
                    <p><?php echo esc_html($tName); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="cancelTerrainBtn" class="btn btn-secondary">Cancel</button>
    </div>
</div>


    <script>
    window.currentPlanetId = <?php echo (int)$planet->getId(); ?>;
    </script>

    <?php
}
