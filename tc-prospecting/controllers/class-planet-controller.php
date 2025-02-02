<?php if (!defined('ABSPATH')) {exit;}

/**
 * Class PlanetController
 *
 * Handles listing, creation, editing, deletion and AJAX-loading of planet views.
 */
class PlanetController {


    public static function init() {
        add_action('wp_ajax_load_planet_view', [self::class, 'loadPlanetView']);
        add_action('wp_ajax_ajax_view_planet', [self::class, 'loadPlanetView']);
    }
    /**
     * Displays the main planet listing page.
     */
    public static function index() {
        global $wpdb;
        $table = $wpdb->prefix . 'tc_prospecting_planets';
        $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC");

        $planets = [];
        foreach ($rows as $row) {
            $p = new Planet(
                $row->id,
                $row->name,
                $row->system,
                $row->sector,
                $row->location,
                (int)$row->size
            );
            $planets[] = $p;
        }
        ?>
        <div class="wrap">
            <a href="?action=planet_creator" class="button button-primary">Create New Planet</a>
            <a href="?action=add_planet" class="button button-secondary" style="margin-left: 10px;">Add Planet from External DB</a>
            <a href="?action=reporting" class="button" style="margin-left: 10px;">Prospecting Reports</a>

            <table class="widefat" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Sector</th>
                        <th>System</th>
                        <th>Location</th>
                        <th>Size</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($planets)): ?>
                        <?php foreach ($planets as $planet): ?>
                            <tr>
                                <td><?php echo esc_html($planet->getId()); ?></td>
                                <td>
                                    <!-- Planet name is rendered as a clickable link -->
                                    <a href="#" class="viewPlanetLink" data-id="<?php echo esc_attr($planet->getId()); ?>">
                                        <?php echo esc_html($planet->getName()); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($planet->getSector()); ?></td>
                                <td><?php echo esc_html($planet->getSystem()); ?></td>
                                <td><?php echo esc_html($planet->getLocation()); ?></td>
                                <td><?php echo esc_html($planet->getSize()); ?></td>
                                <td>
                                    <a href="?action=planet_designer&id=<?php echo $planet->getId(); ?>" class="button">Edit Planet Details</a>
                                    <a href="?action=delete_planet&id=<?php echo $planet->getId(); ?>" class="button" onclick="return confirm('Are you sure you want to delete this planet?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">No planets found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <!-- Container that will be filled by AJAX when a planet name is clicked -->
            <div id="planetViewContainer" class="planet-view-wrapper" style="margin-top: 30px;"></div>
        </div>
        <?php
    }

    /**
     * AJAX handler to load a planet view dynamically.
     * Expects a POST parameter "planet_id" and returns HTML rendered via get_planet_view_html().
     */
    public static function loadPlanetView() {
        // Verify our unified nonce.
        check_ajax_referer('SecurityNonce', 'security');

        $planet_id = intval($_POST['planet_id'] ?? 0);
        if (!$planet_id) {
            wp_send_json_error(['message' => 'No planet ID provided']);
        }
        $planet = Planet::load($planet_id);
        if (!$planet) {
            wp_send_json_error(['message' => 'Planet not found']);
        }
        // Include the view file that defines get_planet_view_html().
        require_once __DIR__ . '/../views/planet-view.php';
        // Capture the rendered HTML.
        ob_start();
        echo get_planet_view_html($planet);
        $html = ob_get_clean();
        wp_send_json_success(['html' => $html]);
    }

    /**
     * Displays the Planet Creator modal/page for creating a new planet.
     */
    public static function showPlanetCreatorModal() {
        ?>
        <div id="planetCreatorModal" class="wrap" style="padding: 20px;">
            <h2>Create New Planet</h2>
            <form method="POST" action="?action=planet_designer">
                <table class="form-table">
                    <tr>
                        <th><label for="planet_name">Planet Name</label></th>
                        <td><input type="text" id="planet_name" name="planet_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="system">System</label></th>
                        <td><input type="text" id="system" name="system" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="sector">Sector</label></th>
                        <td><input type="text" id="sector" name="sector" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="location">Location</label></th>
                        <td><input type="text" id="location" name="location" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="size">Size</label></th>
                        <td>
                            <input type="number" id="size" name="size" class="regular-text" min="1" max="20" required>
                            <p class="description">Value between 1 and 20</p>
                        </td>
                    </tr>
                </table>
                <div style="margin-top: 20px;">
                    <button type="submit" class="button button-primary">Continue to Designer</button>
                    <a href="?action=index" class="button button-secondary" style="margin-left: 10px;">Cancel</a>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Handles the Planet Designer view.
     * If POST, create/update a planet; if GET, load the planet designer view.
     */
    public static function handlePlanetDesigner() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $planetId = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $planet   = ($planetId > 0) ? Planet::load($planetId) : null;

            if ($planet) {
                $planet->setName(sanitize_text_field($_POST['planet_name']));
                $planet->setSystem(sanitize_text_field($_POST['system']));
                $planet->setSector(sanitize_text_field($_POST['sector']));
                $planet->setLocation(sanitize_text_field($_POST['location']));
                $planet->setSize(intval($_POST['size']));
            } else {
                // Create new planet.
                $planet = new Planet(
                    0,
                    sanitize_text_field($_POST['planet_name']),
                    sanitize_text_field($_POST['system']),
                    sanitize_text_field($_POST['sector']),
                    sanitize_text_field($_POST['location']),
                    intval($_POST['size'])
                );
            }

            if ($planet->saveToDatabase()) {
                echo "<script>window.location.href='?action=planet_designer&id={$planet->getId()}';</script>";
                exit;
            } else {
                echo "<div class='notice notice-error'><p>Error saving planet.</p></div>";
            }
        }

        if (isset($_GET['id'])) {
            $planetId = intval($_GET['id']);
            $planet   = Planet::load($planetId);
            if ($planet) {
                require_once __DIR__ . '/../views/planet-designer-view.php';
                render_planet_designer_view($planet);
                return;
            }
        }

        wp_redirect('?action=index');
        exit;
    }
    /**
     * Handles planet deletion.
     */
    public static function handlePlanetDeletion() {
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            wp_die('Invalid Planet ID.');
        }
        $planetId = intval($_GET['id']);
        if (Planet::delete($planetId)) {
            echo "<script>alert('Planet deleted successfully.'); window.location='?action=index';</script>";
        } else {
            echo "<script>alert('Failed to delete planet.'); window.location='?action=index';</script>";
        }
        exit;
    }
    /**
     * Displays the full changelog for a specific deposit (grid).
     * Expects: $_GET['deposit_id'] to indicate which deposit to display logs for.
     */
    public static function displayChangeLog() {
        if ( empty( $_GET['deposit_id'] ) ) {
            wp_die( 'Missing deposit id.' );
        }
        $deposit_id = intval( $_GET['deposit_id'] );

        // Retrieve changelog data using the common helper.
        $logs = ChangelogView::fetchGridChangelogAll($deposit_id);
        ?>
        <div class="wrap">
            <h2>Deposit Change Log</h2>
            <?php if ( empty( $logs ) ) : ?>
                <p>No changelog entries found for this deposit.</p>
            <?php else : ?>
                <table class="widefat" style="margin-top:20px;">
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>User</th>
                            <th>Event Type</th>
                            <th>Event Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $logs as $log ) : ?>
                        <tr>
                            <td><?php echo esc_html( $log['datetime'] ); ?></td>
                            <td><?php echo esc_html( $log['user'] ); ?></td>
                            <td><?php echo esc_html( $log['event_type'] ); ?></td>
                            <td><?php echo esc_html( $log['event'] ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            <p><a href="<?php echo esc_url('/prospecting-database/'); ?>">Back to Dashboard</a></p>
        </div>
        <?php
    }
    public static function displayReports() {
        ?>
        <div class="wrap">
            <hr>
            <h2>Prospecting Reports</h2>
            
            <!-- Filter Selection Buttons -->
        <div style="margin-bottom: 20px;">
            <button class="button report-filter" data-type="system">Systems</button>
            <button class="button report-filter" data-type="sector">Sectors</button>
            <button class="button report-filter" data-type="rm">Raw Materials</button>
            <button class="button report-filter" data-type="prospector">Prospectors</button>
        </div>
    
        <!-- Filter Container -->
        <div id="report-filter-container" style="margin-bottom: 20px;"></div>
    
        <!-- Generate Report Button -->
        <button id="generate-report" class="button button-primary" style="display: none;">
            Generate Report
        </button>
    
        <!-- Report Results Container -->
        <div id="report-results" style="margin-top: 20px;"></div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        let currentFilterType = '';
        let currentFilterValue = '';
        
        // Handle filter button clicks
        $('.report-filter').click(function() {
            currentFilterType = $(this).data('type');
            const container = $('#report-filter-container');
            $('#generate-report').hide();
            $('#report-results').empty();
            container.empty();
    
            const select = $('<select class="filter-select" style="width:300px"></select>');
            const placeholder = {
                system: 'Select a System',
                sector: 'Select a Sector',
                rm: 'Select a Raw Material',
                prospector: 'Select a Prospector'
            }[ currentFilterType ];
            
            select.html('<option>Loading ' + placeholder + '...</option>');
            container.append(select);
    
            // AJAX call to retrieve options from ReportingController
            $.post(tcProspectingData.ajaxurl, {
                action: 'get_report_options',
                filter_type: currentFilterType,
                security: tcProspectingData.nonce
            }, function(response) {
                if (response.success) {
                    select.empty().append('<option></option>');
                    response.data.forEach(function(item) {
                        select.append(new Option(item.value, item.id || item.value));
                    });
                    select.select2({
                        placeholder: placeholder,
                        allowClear: true
                    });
                }
            }, 'json');
        });
        
        // When the select value changes, show the Generate Report button
        $(document).on('change', '.filter-select', function() {
            currentFilterValue = $(this).val();
            $('#generate-report').toggle(!!currentFilterValue);
        });
        
        // Generate report based on the selected filter
        $('#generate-report').click(function() {
            $.post(tcProspectingData.ajaxurl, {
                action: 'generate_report',
                filter_type: currentFilterType,
                filter_value: currentFilterValue,
                security: tcProspectingData.nonce
            }, function(response) {
                if (response.success) {
                    renderReport(response.data);
                }
            }, 'json');
        });
        
        // Function to render the report from returned data
        function renderReport(data) {
            const container = $('#report-results').empty();
            let html = '';
            if (data.type === 'system' || data.type === 'sector') {
                html += '<h3>Report for ' + data.filter_value + '</h3>';
                html += '<table class="widefat"><thead><tr><th>Material</th><th>Deposit Count</th><th>Total Amount</th></tr></thead><tbody>';
                data.data.forEach(function(row) {
                    html += '<tr><td>' + row.deposit_type + '</td><td>' + row.count + '</td><td>' + row.total + '</td></tr>';
                });
                html += '</tbody></table>';
            } else if (data.type === 'rm') {
                html += '<h3>' + data.filter_value + ' Distribution</h3>';
                html += '<table class="widefat"><thead><tr><th>Planet</th><th>System</th><th>Sector</th><th>Deposit Count</th><th>Total Amount</th></tr></thead><tbody>';
                data.data.forEach(function(row) {
                    html += '<tr><td>' + row.name + '</td><td>' + row.system + '</td><td>' + row.sector + '</td><td>' + row.deposit_count + '</td><td>' + row.total_amount + '</td></tr>';
                });
                html += '</tbody></table>';
            } else if (data.type === 'prospector') {
                html += '<h3>Prospector Report: ' + data.filter_value + '</h3>';
                html += '<div class="prospector-stats">';
                html += '<p>Total Grids Prospected: ' + data.data.total_grids + '</p>';
                html += '<p>Total Deposits Found: ' + data.data.total_deposits + '</p>';
                html += '<h4>Materials Breakdown</h4>';
                html += '<table class="widefat"><thead><tr><th>Material</th><th>Deposit Count</th><th>Total Amount</th></tr></thead><tbody>';
                data.data.rm_breakdown.forEach(function(row) {
                    html += '<tr><td>' + row.deposit_type + '</td><td>' + row.count + '</td><td>' + row.total + '</td></tr>';
                });
                html += '</tbody></table></div>';
            }
            container.html(html);
        }
    });
    </script>
    <?php
    }
}
PlanetController::init();