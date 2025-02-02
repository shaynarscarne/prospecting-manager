<?php if (!defined('ABSPATH')) {exit;}

/**
 * Renders a single planet's HTML view.
 *
 * This includes:
 *  - Deposit summary & statistics
 *  - Terrain grid with deposit indicators
 *  - Modals for adding/modifying deposits and XML upload.
 *
 * The function assumes that the $planet passed in is a valid Planet object.
 *
 * @param Planet $planet
 * @return string HTML output
 */
function get_planet_view_html(Planet $planet) {
    $terrain_map   = $planet->getTerrainData();
    $deposit_map   = $planet->getDepositData();
    $all_deposits  = $planet->getAllDeposits();

    $depositCounts = [];
    $depositTotals = [];
    $totalCount    = 0;
    $totalSize     = 0;
    foreach ($all_deposits as $dep) {
        $type = $dep->getDepositType();
        $size = $dep->getSize();
        if (!isset($depositCounts[$type])) {
            $depositCounts[$type] = 0;
            $depositTotals[$type] = 0;
        }
        $depositCounts[$type]++;
        $depositTotals[$type] += $size;
        $totalCount++;
        $totalSize += $size;
    }

    $nonVolcano = 0;
    foreach ($terrain_map as $terrain) {
        if (strtolower($terrain) !== 'volcano') {
            $nonVolcano++;
        }
    }
    $prospectedCount = count($deposit_map);
    $size = $planet->getSize();

    $current_user = wp_get_current_user();
    $logged_in_user_name = $current_user->user_login ?: 'unknown';

    ob_start();
    ?>
    <div class="planet-view-container">
      <h2>Planet View - <?php echo esc_html($planet->getName()); ?></h2>
      <!-- Tab Navigation -->
      <div class="planet-tabs" style="margin-bottom:15px;">
          <button class="button" onclick="openTab('planetDetailsTab')">Planet Details</button>
          <button class="button" onclick="openTab('planetChangelogTab')">Changelog</button>
          <button class="button" onclick="openXmlUploadModal()">Upload XML Data</button>
      </div>

      <!-- Planet Details Tab -->
      <div id="planetDetailsTab" style="display:block;">
          <?php if (!empty($all_deposits)): ?>
          <div class="deposit-summary" style="margin-bottom:20px; padding:10px; background:#f9f9f9; border:1px solid #ddd;">
              <h3>Deposit Summary</h3>
              <table class="widefat" style="max-width:600px;">
                  <thead>
                      <tr>
                          <th>Deposit Type</th>
                          <th>Count (pct)</th>
                          <th>Total (pct)</th>
                      </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($depositCounts as $type => $count): 
                      $pctCount = $totalCount > 0 ? number_format(($count / $totalCount) * 100, 2) . '%' : '0%';
                      $sumSize  = $depositTotals[$type];
                      $pctSize  = $totalSize > 0 ? number_format(($sumSize / $totalSize) * 100, 2) . '%' : '0%';
                    ?>
                      <tr>
                          <td><?php echo esc_html($type); ?></td>
                          <td><?php echo $count; ?> (<?php echo $pctCount; ?>)</td>
                          <td><?php echo $sumSize; ?>m³ (<?php echo $pctSize; ?>)</td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
              </table>
              <p style="margin-top:10px;">
                  Non-volcano squares: <?php echo intval($nonVolcano); ?><br>
                  Squares prospected: <?php echo intval($prospectedCount); ?>
              </p>
          </div>
          <?php endif; ?>

          <!-- Terrain and Deposits Grid -->
          <div id="terrain-grid">
              <table class="terrain-table">
                  <tr>
                      <th class='terrainTH'></th>
                      <?php for ($x = 0; $x < $size; $x++): ?>
                          <th style="padding:0; text-align:center;"><?php echo $x; ?></th>
                      <?php endfor; ?>
                  </tr>
                  <?php for ($y = 0; $y < $size; $y++): ?>
                      <tr>
                          <th class='terrainTH'><?php echo $y; ?></th>
                          <?php for ($x = 0; $x < $size; $x++):
                              $key = "{$x}_{$y}";
                              $terrain_type = $terrain_map[$key] ?? null;
                              $deposit_data = $deposit_map[$key] ?? null;
                    
                              $terrain_img = $terrain_type 
                                  ? "<img src='https://images.swcombine.com/galaxy/terrains/" . esc_attr($terrain_type) . "/terrain.gif' height='60' width='60' alt='" . esc_attr($terrain_type) . "'>" 
                                  : '';
                    
                              $deposit_img = '';
                              if ($deposit_data) {
                                  $res_code = Deposit::getResourceCode($deposit_data['type']);
                                  if ($res_code && $res_code != 9999) {
                                      $deposit_img = "<img src='https://images.swcombine.com/materials/" . esc_attr($res_code) . "/deposit.gif' height='60' width='60' style='position:absolute; top:0; left:0; cursor:pointer;' class='deposit-modify' alt='" . esc_attr($deposit_data['type']) . "' data-deposit-id='" . esc_attr($deposit_data['id']) . "' data-resource-type='" . esc_attr($deposit_data['type']) . "' data-resource-amount='" . esc_attr($deposit_data['size']) . "' data-prospector='" . esc_attr($deposit_data['prospector']) . "' data-prospecting-time='" . esc_attr($deposit_data['prospecting_time']) . "' data-last-updated='" . esc_attr($deposit_data['last_updated']) . "'>";
                                  } else {
                                      $deposit_img = "<img src='https://cdn-icons-png.flaticon.com/512/1828/1828843.png' height='60' width='60' style='position:absolute; top:0; left:0; cursor:pointer; opacity:0.3;' class='deposit-modify' alt='No deposit' data-deposit-id='" . esc_attr($deposit_data['id']) . "' data-resource-type='" . esc_attr($deposit_data['type']) . "' data-resource-amount='" . esc_attr($deposit_data['size']) . "' data-prospector='" . esc_attr($deposit_data['prospector']) . "' data-prospecting-time='" . esc_attr($deposit_data['prospecting_time']) . "' data-last-updated='" . esc_attr($deposit_data['last_updated']) . "'>";
                                  }
                              }
                          ?>
                          <td class="terrainTD"" onclick="handleGridClick(this, <?php echo $planet->getId(); ?>, <?php echo $x; ?>, <?php echo $y; ?>)">
                              <div style="position:relative; display:inline-block;">
                                  <?php echo $terrain_img; ?>
                                  <?php echo $deposit_img; ?>
                              </div>
                          </td>
                          <?php endfor; ?>
                      </tr>
                  <?php endfor; ?>
              </table>
          </div>
      </div>

      <!-- Changelog Tab (Initially hidden) -->
      <div id="planetChangelogTab" style="display:none;">
          <h3>Planet Changelog</h3>
          <p>Below is the recorded history for this planet (newest first).</p>
          <ul id="planetChangelogList" style="list-style-type:none; padding-left:0;"></ul>
      </div>
    </div>

    <!-- Modals -->

    <!-- Resource Selection Modal (New Deposit) -->
    <div id="resourceModal" class="modal">
      <div class="modal-content">
        <h3>Add Resource</h3>
        <div id="resourceGrid" class="resource-grid">
          <?php
          $resources = [
              'Quantum'    => 1,
              'Meleenium'  => 2,
              'Ardanium'   => 3,
              'Rudic'      => 4,
              'Ryll'       => 5,
              'Duracrete'  => 6,
              'Alazhi'     => 7,
              'Laboi'      => 8,
              'Adegan'     => 9,
              'Rockivory'  => 10,
              'Tibannagas'=> 11,
              'Nova'       => 12,
              'Varium'     => 13,
              'Varmigio'   => 14,
              'Lommite'    => 15,
              'Hibridium'  => 16,
              'Durelium'   => 17,
              'Lowickan'   => 18,
              'Vertex'     => 19,
              'Berubian'   => 20
          ];
          foreach ($resources as $name => $code) {
              echo "<div class='resource-option' data-resource-name='" . esc_attr($name) . "' onclick='selectResource(\"{$name}\", \"{$code}\", this)'>";
              echo "<img src='https://images.swcombine.com/materials/{$code}/deposit.gif' alt='" . esc_attr($name) . "' height='32' width='32'>";
              echo "<p style='margin: 5px 0;'>{$name}</p>";
              echo "</div>";
          }
          ?>
          <div class="resource-option" data-resource-name="No deposit" onclick="selectResource('No deposit', '0', this)">
            <img src="https://cdn-icons-png.flaticon.com/512/1828/1828843.png" alt="No deposit" height="32" width="32">
            <p>No Deposit</p>
          </div>
        </div>
        <div style="margin-top: 20px;">
          <p>
            <label for="resourceAmount">Amount:</label>
            <input type="number" id="resourceAmount" min="1" class="regular-text">
          </p>
          <p>
            <label for="prospectorName">Prospector:</label>
            <input type="text" id="prospectorName" class="regular-text" value="<?php echo esc_attr($logged_in_user_name); ?>">
          </p>
          <p>
            <label for="prospectingTime">Prospecting Time:</label>
            <input type="datetime-local" id="prospectingTime" class="regular-text">
          </p>
          <p>
            <label for="prospectorSkill">Prospector Skill:</label>
            <input type="number" id="prospectorSkill" min="0" max="10" value="5" class="regular-text">
          </p>
          <p>
            <label for="prospectorVehicle">Prospector Vehicle:</label>
            <select id="prospectorVehicle" class="regular-text">
              <option value="SX-65 Groundhog" selected>SX-65 Groundhog</option>
              <option value="FK-47 Airspeeder">FK-47 Airspeeder</option>
            </select>
          </p>
          <button id="saveResourceBtn" class="button-primary">Save</button>
          <button onclick="closeResourceModal()" class="button-secondary">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Modify Deposit Modal -->
    <div id="modifyDepositModal" class="modal">
      <div class="modal-content">
        <h3>Modify Deposit</h3>
        <div class="modal-header">
          <label class="last-updated-label">Last Updated:</label>
          <span id="lastUpdatedValue"></span>
          <a href="#" id="depositChangelogLink" style="text-decoration: none; font-size: 16px; margin-left: 10px;">
            <span class="dashicons dashicons-book-alt"></span>
          </a>
        </div>
        <div id="changelogContainer" class="changelog-container">
          <h4>Last 3 Changes:</h4>
          <ul id="changelogList" style="list-style-type:none; padding-left:0;"></ul>
        </div>
        <input type="hidden" id="modifyDepositId">
        <!-- Resource grid for modifying deposit (populated by JS) -->
        <div id="modifyResourceGrid" class="resource-grid"></div>
        <p>
          <label for="modifyResourceAmount">Amount:</label>
          <input type="number" id="modifyResourceAmount" min="0" class="regular-text">
        </p>
        <p>
          <label for="modifyProspectorName">Prospector:</label>
          <input type="text" id="modifyProspectorName" class="regular-text">
        </p>
        <p>
          <label for="modifyProspectingTime">Prospecting Time:</label>
          <input type="datetime-local" id="modifyProspectingTime" class="regular-text">
        </p>
        <p>
          <label for="modifyProspectorSkill">Prospector Skill:</label>
          <input type="number" id="modifyProspectorSkill" min="0" max="10" value="5" class="regular-text">
        </p>
        <p>
          <label for="modifyProspectorVehicle">Prospector Vehicle:</label>
          <select id="modifyProspectorVehicle" class="regular-text">
            <option value="SX-65 Groundhog" selected>SX-65 Groundhog</option>
            <option value="FK-47 Airspeeder">FK-47 Airspeeder</option>
          </select>
        </p>
        <button id="modifyResourceBtn" class="button-primary">Save Changes</button>
        <button onclick="closeModifyModal()" class="button-secondary">Cancel</button>
        <button id="deleteResourceBtn" class="button-secondary" style="float:right;">Delete Deposit</button>
      </div>
    </div>

    <!-- XML Upload Modal -->
    <div id="xmlUploadModal" class="modal">
      <div class="modal-content">
        <h3>Upload Deposit XML</h3>
        <form id="xmlUploadForm" method="post" enctype="multipart/form-data">
          <input type="file" id="xmlFile" name="xml_file" accept=".xml,.rss">
          <input type="hidden" name="planet_id" value="<?php echo esc_attr($planet->getId()); ?>">
          <input type="hidden" name="security" value="<?php echo wp_create_nonce('SecurityNonce'); ?>">
          <button type="button" id="processXmlBtn" class="button-primary">Process XML</button>
          <button type="button" id="cancelXmlBtn" class="button-secondary">Cancel</button>
        </form>
        <div id="xmlUploadStatus" style="margin-top: 10px;">
            <div id="loadingSpinner" style="display: none;"></div>
        </div>
      </div>
    </div>
    <?php
    return ob_get_clean();
}
?>
