<?php
/*
if (!defined('ABSPATH')) {
    exit;
}
*/
/**
 * A single Deposit on a planet at (x, y).
 * Tracks deposit type, size, prospector info, etc.
 */
class Deposit {
    private int $id;
    private int $planetId;
    private int $x;
    private int $y;
    private string $depositType;
    private int $size;
    private ?DateTime $prospectingTime;
    private ?string $prospector;
    private ?DateTime $lastUpdated;
    private ?string $lastUpdater;
    private int $prospectorSkill;
    private string $prospectorVehicle;

    /**
     * Constructor
     * @param int $id The deposit's DB ID. 0 or negative if new
     */
    public function __construct(int $id, int $planetId = 0, int $x = 0, int $y = 0, string $depositType = '', int $size = 0, ?DateTime $prospectingTime = null, ?string $prospector = null, ?DateTime $lastUpdated = null, ?string $lastUpdater = null, int $prospectorSkill = 5, string $prospectorVehicle = 'SX-65 Groundhog') {
        $this->id                = $id;
        $this->planetId          = $planetId;
        $this->x                 = $x;
        $this->y                 = $y;
        $this->depositType       = $depositType;
        $this->size              = $size;
        $this->prospectingTime   = $prospectingTime;
        $this->prospector        = $prospector;
        $this->lastUpdated       = $lastUpdated;
        $this->lastUpdater       = $lastUpdater;
        $this->prospectorSkill   = $prospectorSkill;
        $this->prospectorVehicle = $prospectorVehicle;
    }

    /**
     * Load this deposit from the database by $this->id.
     */
    public function loadFromDatabase(): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'tc_prospecting_deposits';

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id=%d",
            $this->id
        ));
        if (!$row) {
            return false;
        }

        $this->planetId         = (int) $row->planet_id;
        $this->x                = (int) $row->x;
        $this->y                = (int) $row->y;
        $this->depositType      = (string) $row->deposit_type;
        $this->size             = (int) $row->size;
        $this->prospectingTime  = $row->prospecting_time ? new DateTime($row->prospecting_time) : null;
        $this->prospector       = $row->prospector;
        $this->lastUpdated      = $row->last_updated ? new DateTime($row->last_updated) : null;
        $this->lastUpdater      = $row->last_updater;
        $this->prospectorSkill   = isset($row->prospector_skill) ? (int) $row->prospector_skill : 5;
        $this->prospectorVehicle = isset($row->prospector_vehicle) ? (string) $row->prospector_vehicle : 'SX-65 Groundhog';

        return true;
    }

    /**
     * Insert or update this deposit. Uses logger to detect creation or modification.
     */
    public function saveToDatabase(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'tc_prospecting_deposits';

        // Check if we exist
        $oldDeposit = null;
        $exists     = false;
        if ($this->id > 0) {
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $this->id));
            if ($row) {
                $exists     = true;
                $oldDeposit = new static(
                    (int) $row->id,
                    (int) $row->planet_id,
                    (int) $row->x,
                    (int) $row->y,
                    (string) $row->deposit_type,
                    (int) $row->size,
                    $row->prospecting_time ? new DateTime($row->prospecting_time) : null,
                    $row->prospector,
                    $row->last_updated ? new DateTime($row->last_updated) : null,
                    $row->last_updater,
                    isset($row->prospector_skill) ? (int)$row->prospector_skill : 5,
                    isset($row->prospector_vehicle) ? (string)$row->prospector_vehicle : 'SX-65 Groundhog'
                );
            }
        }

        $data = [
            'planet_id'         => $this->planetId,
            'x'                 => $this->x,
            'y'                 => $this->y,
            'deposit_type'      => $this->depositType,
            'size'              => $this->size,
            'prospecting_time'  => $this->prospectingTime ? $this->prospectingTime->format('Y-m-d H:i:s') : null,
            'prospector'        => $this->prospector,
            'last_updated'      => $this->lastUpdated ? $this->lastUpdated->format('Y-m-d H:i:s') : null,
            'last_updater'      => $this->lastUpdater,
            'prospector_skill'  => $this->prospectorSkill,
            'prospector_vehicle'=> $this->prospectorVehicle
        ];

        if ($exists) {
            $wpdb->update($table, $data, ['id' => $this->id]);
        } else {
            $wpdb->insert($table, $data);
            $this->id = $wpdb->insert_id;
        }

        // Use logger
        if (!$exists) {
            $this->logCreation();
        } else {
            $changes = ProspectingLogger::compareChanges(
                $oldDeposit,
                $this,
                [
                    'depositType' => 'Type',
                    'size'        => 'Size',
                    'prospectorSkill'   => 'Prospector Skill',
                    'prospectorVehicle' => 'Prospector Vehicle'
                ]
            );
            if (!empty($changes)) {
                $planetName = Planet::load($this->planetId)?->getName() ?? '';
                $msg = sprintf(
                    'Updated deposit on planet %s at (%d, %d): %s',
                    $planetName,
                    $this->x,
                    $this->y,
                    implode('; ', $changes)
                );
                ProspectingLogger::logEvent([
                    'object_type' => 'deposit',
                    'object_id'   => $this->id,
                    'planet_id'   => $this->planetId,
                    'event_type'  => 'MODIFIED_DEPOSIT',
                    'event'       => $msg
                ]);
            }
        }
    }

    /**
     * Delete this deposit, logging a DELETED_DEPOSIT event.
     */
    public function delete(): bool {
        global $wpdb;
        if ($this->id <= 0) {
            return false;
        }

        $planetName = Planet::load($this->planetId)?->getName() ?? '';
        $msg = sprintf(
            'Deleted deposit of %s on planet %s at coords (%d, %d)',
            $this->depositType,
            $planetName,
            $this->x,
            $this->y
        );
        ProspectingLogger::logEvent([
            'object_type' => 'deposit',
            'object_id'   => $this->id,
            'planet_id'   => $this->planetId,
            'event_type'  => 'DELETED_DEPOSIT',
            'event'       => $msg
        ]);

        $deleted = $wpdb->delete(
            "{$wpdb->prefix}tc_prospecting_deposits",
            ['id' => $this->id],
            ['%d']
        );
        return (bool)$deleted;
    }

    /**
     * Helper: log the creation of this deposit.
     */
    private function logCreation(): void {
        $planetName = Planet::load($this->planetId)?->getName() ?? '';
        $msg = '';
        if (strcasecmp($this->depositType, 'No deposit') === 0) {
            $msg = sprintf(
                'Found no deposit using skill %d and %s on planet %s coords (%d, %d)',
                $this->prospectorSkill,
                $this->prospectorVehicle,
                $planetName,
                $this->x,
                $this->y
            );
        } else {
            $msg = sprintf(
                'Added %dm³ of %s at coords (%d, %d) on planet %s',
                $this->size,
                $this->depositType,
                $this->x,
                $this->y,
                $planetName
            );
        }
        ProspectingLogger::logEvent([
            'object_type' => 'deposit',
            'object_id'   => $this->id,
            'planet_id'   => $this->planetId,
            'event_type'  => 'CREATED_DEPOSIT',
            'event'       => $msg
        ]);
    }

    /**
     * Shortcut to set last updated in DB on size changes.
     */
    public function updateSize(int $newSize): void {
        $this->size = $newSize;
        $this->lastUpdated = new DateTime();
        $currentUser = wp_get_current_user();
        $this->lastUpdater = $currentUser->user_login ?: 'Unknown';
        $this->saveToDatabase();
    }

    /**
     * Return all deposits for a planet.
     */
    public static function getDepositsByPlanet(int $planetId): array {
        global $wpdb;
        $table = $wpdb->prefix . 'tc_prospecting_deposits';

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM $table WHERE planet_id=%d",
            $planetId
        ));

        $out = [];
        foreach ($rows as $r) {
            $dep = new static((int) $r->id);
            if ($dep->loadFromDatabase()) {
                $out[] = $dep;
            }
        }
        return $out;
    }

    /**
     * Return deposit for a given planet and coordinates.
     */
    public static function getDepositsByGrid(int $planetId, int $x, int $y): array {
        global $wpdb;
        $table = $wpdb->prefix . 'tc_prospecting_deposits';

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id FROM $table WHERE planet_id=%d AND x=%d AND y=%d",
            $planetId,
            $x,
            $y
        ));

        $out = [];
        foreach ($rows as $r) {
            $dep = new static((int) $r->id);
            if ($dep->loadFromDatabase()) {
                $out[] = $dep;
            }
        }
        return $out;
    }

    /**
     * Mapping deposit names to SWC image codes, optional "No deposit" as 9999
     */
    public static function getResourceCode(string $res): ?int {
        static $map = [
            'Quantum'     => 1, 'Meleenium' => 2, 'Ardanium' => 3,
            'Rudic'       => 4, 'Ryll' => 5, 'Duracrete' => 6,
            'Alazhi'      => 7, 'Laboi' => 8,  'Adegan' => 9,
            'Rockivory'   => 10,'Tibannagas' => 11,'Nova' => 12,
            'Varium'      => 13,'Varmigio' => 14,'Lommite' => 15,
            'Hibridium'   => 16,'Durelium' => 17,'Lowickan' => 18,
            'Vertex'      => 19,'Berubian' => 20,'No deposit'=>9999
        ];
        return $map[$res] ?? null;
    }

    public function getId(): int                   { return $this->id; }
    public function getPlanetId(): int             { return $this->planetId; }
    public function getX(): int                    { return $this->x; }
    public function getY(): int                    { return $this->y; }
    public function getDepositType(): string       { return $this->depositType; }
    public function setDepositType(string $t): void { $this->depositType = $t; }
    public function getSize(): int          { return $this->size; }
    public function getProspectingTime(): ?DateTime { return $this->prospectingTime; }
    public function setProspectingTime(?DateTime $dt): void { $this->prospectingTime = $dt; }
    public function getProspector(): ?string       { return $this->prospector; }
    public function setProspector(?string $p): void { $this->prospector = $p; }
    public function getLastUpdated(): ?DateTime    { return $this->lastUpdated; }
    public function setLastUpdated(?DateTime $dt): void { $this->lastUpdated = $dt; }
    public function getLastUpdater(): ?string      { return $this->lastUpdater; }
    public function setLastUpdater(?string $lu): void { $this->lastUpdater = $lu; }
    public function getProspectorSkill(): int      { return $this->prospectorSkill; }
    public function setProspectorSkill(int $s): void { $this->prospectorSkill = $s; }
    public function getProspectorVehicle(): string { return $this->prospectorVehicle; }
    public function setProspectorVehicle(string $v): void { $this->prospectorVehicle = $v; }
}
