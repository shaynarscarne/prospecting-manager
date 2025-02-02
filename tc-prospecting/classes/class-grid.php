<?php if (!defined('ABSPATH')) {exit;}

/**
 * A Grid is a single coordinate on a planet that tracks terrain type.
 * Each (planetId, x, y) is unique, and only one deposit can exist per Grid.
 */
class Grid {
    private int $id = 0;
    private int $planetId;
    private int $x;
    private int $y;
    private string $terrainType;

    /**
     * @param int $planetId ID of the planet this grid belongs to
     * @param int $x X-coordinate
     * @param int $y Y-coordinate
     * @param string $terrainType Terrain code or name (e.g. "forest", "desert")
     */
    public function __construct(
        int $planetId,
        int $x,
        int $y,
        string $terrainType = 'unknown'
    ) {
        $this->planetId    = $planetId;
        $this->x           = $x;
        $this->y           = $y;
        $this->terrainType = $terrainType;
    }

    /**
     * Attempts to load this grid from the database using planetId, x, y.
     * @return bool True if found, false otherwise
     */
    public function loadFromDatabase(): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'tc_prospecting_grids';

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE planet_id=%d AND x=%d AND y=%d",
            $this->planetId,
            $this->x,
            $this->y
        ));

        if ($row) {
            $this->id          = (int)$row->id;
            $this->terrainType = (string)$row->terrain_type;
            return true;
        }
        return false;
    }

    /**
     * Inserts or updates this grid in the database.
     * Logs CREATE_GRID or MODIFIED_GRID if the terrain changes.
     */
    public function saveToDatabase(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'tc_prospecting_grids';

        $exists  = false;
        $oldTerrain = null;

        if ($this->id > 0) {
            $oldRow = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $this->id));
            if ($oldRow) {
                $exists     = true;
                $oldTerrain = (string)$oldRow->terrain_type;
            }
        }

        $data = [
            'planet_id'    => $this->planetId,
            'x'            => $this->x,
            'y'            => $this->y,
            'terrain_type' => $this->terrainType
        ];

        $wpdb->replace($table, $data);
        if ($this->id === 0) {
            $this->id = $wpdb->insert_id;
        }

        if (!$exists) {
            $planetName = Planet::load($this->planetId)?->getName() ?? '';
            $eventText  = sprintf(
                'Created grid on planet %s at (%d,%d) with terrain %s',
                $planetName, $this->x, $this->y, $this->terrainType
            );
            ProspectingLogger::logEvent([
                'object_type' => 'grid',
                'object_id'   => $this->id,
                'planet_id'   => $this->planetId,
                'event_type'  => 'CREATE_GRID',
                'event'       => $eventText
            ]);
        } else if ($oldTerrain !== $this->terrainType) {
            $planetName = Planet::load($this->planetId)?->getName() ?? '';
            $eventText  = sprintf(
                'Changed terrain on planet %s at (%d,%d) from %s to %s',
                $planetName, $this->x, $this->y, $oldTerrain, $this->terrainType
            );
            ProspectingLogger::logEvent([
                'object_type' => 'grid',
                'object_id'   => $this->id,
                'planet_id'   => $this->planetId,
                'event_type'  => 'MODIFIED_GRID',
                'event'       => $eventText
            ]);
        }
    }

    /**
     * Deletes this grid from the database, logs a DELETED_GRID event.
     * @return bool True on success, false on failure
     */
    public function delete(): bool {
        global $wpdb;
        $planetName = Planet::load($this->planetId)?->getName() ?? '';
        $eventText  = sprintf(
            'Deleted grid at coordinates (%d,%d) on planet %s',
            $this->x, $this->y, $planetName
        );
        ProspectingLogger::logEvent([
            'object_type' => 'grid',
            'object_id'   => $this->id,
            'planet_id'   => $this->planetId,
            'event_type'  => 'DELETED_GRID',
            'event'       => $eventText
        ]);

        $deleted = $wpdb->delete(
            $wpdb->prefix . 'tc_prospecting_grids',
            ['id' => $this->id],
            ['%d']
        );
        return (bool)$deleted;
    }

    public function getId(): int {return $this->id;}
    public function getPlanetId(): int {return $this->planetId;}
    public function getX(): int {return $this->x;}
    public function getY(): int {return $this->y;}
    public function getTerrainType(): string {return $this->terrainType;}
    public function setTerrainType(string $terrainType): void {$this->terrainType = $terrainType;}
}
