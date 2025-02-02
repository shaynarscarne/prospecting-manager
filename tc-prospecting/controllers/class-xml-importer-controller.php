<?php if (!defined('ABSPATH')) {exit;}

class XMLImporterController {
    public static function init() {
        add_action('wp_ajax_process_xml_upload', [self::class, 'handleXMLUpload']);
        
    }

    /**
     * Handle the XML upload. 
     * Expects a file input "xml_file" and a valid nonce "xml_upload_nonce".
     */
    public static function handleXMLUpload() {
        check_ajax_referer('SecurityNonce', 'security');

        global $wpdb;
        try {
            if (empty($_FILES['xml_file']['tmp_name'])) {
                throw new Exception('No file uploaded. Debug: ' . print_r($_FILES, true));
            }
            if (!is_readable($_FILES['xml_file']['tmp_name'])) {
                throw new Exception('Uploaded file is not readable.');
            }

            $content = file_get_contents($_FILES['xml_file']['tmp_name']);

            // Ensure it has an XML declaration, since deposit XMLs donøt come with it by default.
            if (!str_contains($content, '<?xml')) {
                $content = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $content;
            }
            $content = mb_convert_encoding($content, 'UTF-8', 'auto');

            $xml = simplexml_load_string($content);
            if (!$xml) {
                throw new Exception('Failed to parse XML. Invalid format.');
            }

            $wpdb->query('START TRANSACTION');

            $planetName = (string)$xml['planet_name'];
            $planet     = Planet::load($planetName);
            if (!$planet) {
                throw new Exception("Planet '$planetName' not found in the database.");
            }

            // Get the planet_id submitted with the form and verify it matches the XML planet.
            if (!isset($_POST['planet_id'])) {
                throw new Exception('Planet ID not provided in the upload.');
            }
            $postedPlanetId = intval($_POST['planet_id']);
            if ($planet->getId() !== $postedPlanetId) {
                throw new Exception('Planet mismatch: The uploaded XML does not correspond to the current planet.');
            }

            // Track (x,y) pairs to handle deletion of missing deposits
            $xmlPairs = [];

            foreach ($xml->deposit as $depositNode) {
                $x       = (int)$depositNode['x'];
                $y       = (int)$depositNode['y'];
                $type    = ucfirst((string)$depositNode->type);
                $amount  = (int)$depositNode->size;
                $xyKey   = "{$x}_{$y}";
                $xmlPairs[] = $xyKey;

                // Check if deposit for (planetId, x, y) already exists
                $existing = Deposit::getDepositsByGrid($planet->getId(), $x, $y);
                if (!empty($existing)) {
                    $d = $existing[0];
                    $d->setDepositType($type);
                    $d->updateSize($amount);
                } else {
                    $d = new Deposit(
                        0, 
                        $planet->getId(),
                        $x,
                        $y,
                        $type,
                        $amount,
                        null, 
                        'XML Upload',
                        null,
                        null,
                        5,  
                        'SX-65 Groundhog'
                    );
                    $d->saveToDatabase();
                }
            }

            // Look at all existing deposits on the planet, and remove those not present in the XML
            $allDeposits = $planet->getAllDeposits();
            $deletedCount = 0;
            foreach ($allDeposits as $dep) {
                $k = "{$dep->getX()}_{$dep->getY()}";
                if (!in_array($k, $xmlPairs, true)) {
                    $dep->delete(); 
                    $deletedCount++;
                }
            }

            $wpdb->query('COMMIT');

            wp_send_json_success([
                'message' => sprintf(
                    'Processed %d deposits, deleted %d entries',
                    count($xmlPairs),
                    $deletedCount
                )
            ]);
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}
XMLImporterController::init();