<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin Name: TC Prospecting Database
 * Description: Track prospecting & mining efforts. Planet creation, designing, deposit logs, etc.
 * Version: 1.0
 * Requires PHP: 7.4
 * Author: Shay
 */

function tc_prospecting_enqueue_scripts() {
    global $wpdb;
    $nonce           = wp_create_nonce( 'SecurityNonce' );
    $current_user    = wp_get_current_user();
    $logged_in_user  = $current_user->user_login ? $current_user->user_login : 'unknown';
    $current_time    = current_time( 'Y-m-d\TH:i' );
    $planets         = $wpdb->get_results( "SELECT uid, name FROM swc_planets ORDER BY name ASC" );

    wp_enqueue_script( 'jquery' , array(), null, false);

    // Enqueue select2 script and style using the proper plugin path
    wp_enqueue_script(
        'select2-custom',
        plugins_url( '/assets/select2.min.js', __FILE__ ),
        array( 'jquery' ),
        null,
        false
    );
    wp_enqueue_style(
        'select2-custom',
        plugins_url( '/assets/select2.min.css', __FILE__ )
    );

    // Enqueue our main prospecting scripts and styles
    wp_enqueue_script(
        'tc-prospecting-js',
        plugin_dir_url( __FILE__ ) . 'assets/prospecting.js',
        array( 'jquery' ),
        null,
        true
    );
    wp_enqueue_script(
        'planet-designer-js',
        plugin_dir_url( __FILE__ ) . 'assets/planet-designer.js',
        array( 'jquery' ),
        null,
        true
    );
    wp_enqueue_style(
        'tc-prospecting-style',
        plugin_dir_url( __FILE__ ) . '/assets/style.css',
        array(),
        true
    );

    // Localize scripts with data
    wp_localize_script( 'select2-custom', 'planetData', array(
        'planets' => $planets,
        'nonce'   => $nonce,
        'ajaxurl' => admin_url( 'admin-ajax.php' )
    ) );

    wp_localize_script( 'planet-designer-js', 'tcProspectingData', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => $nonce
    ) );

    wp_localize_script( 'tc-prospecting-js', 'tcProspectingData', array(
        'ajaxurl'     => admin_url( 'admin-ajax.php' ),
        'nonce'       => $nonce,
        'currentUser' => $logged_in_user,
        'currentTime' => $current_time
    ) );
}
add_action( 'wp_enqueue_scripts', 'tc_prospecting_enqueue_scripts' );

// Include necessary classes and controllers
require_once __DIR__ . '/classes/class-prospecting-logger.php';
require_once __DIR__ . '/classes/class-planet.php';
require_once __DIR__ . '/classes/class-grid.php';
require_once __DIR__ . '/classes/class-deposit.php';

require_once __DIR__ . '/controllers/class-planet-controller.php';
require_once __DIR__ . '/controllers/class-xml-importer-controller.php';
require_once __DIR__ . '/controllers/class-planet-importer-controller.php';
require_once __DIR__ . '/controllers/class-planet-designer-controller.php';
require_once __DIR__ . '/controllers/class-deposits-controller.php';
require_once __DIR__ . '/controllers/class-changelog-controller.php';
require_once __DIR__ . '/controllers/class-report-controller.php';

function tc_prospecting_main() {
    if ( isset( $_GET['action'] ) ) {
        switch ( $_GET['action'] ) {
            case 'planet_designer':
                PlanetController::handlePlanetDesigner();
                break;
            case 'delete_planet':
                PlanetController::handlePlanetDeletion();
                break;
            case 'planet_creator':
                PlanetController::showPlanetCreatorModal();
                break;
            case 'add_planet':
                PlanetImportController::addPlanet();
                break;
            case 'reporting':
                PlanetController::index();
                PlanetController::displayReports();
                break;
            case 'grid_changelog':
                PlanetController::displayChangeLog();
                break;
            default:
                PlanetController::index();
        }
    } else {
        PlanetController::index();
    }
}

add_shortcode( 'main_prospecting', 'tc_prospecting_main' );
