<?php
class Planet {
    private int $id = 0;
    private string $name;
    private string $system;
    private string $sector;
    private string $location;
    private int $size;
    private array $grids = [];

    public function __construct(int $id = 0, string $name = '', string $system = '', string $sector = '', string $location = '', int $size = 0) {
        $this->id = $id;
        $this->name = $name;
        $this->system = $system;
        $this->sector = $sector;
        $this->location = $location;
        $this->size = $size;
    }

    public static function load(int|string $identifier): ?Planet {
        global $wpdb;
        $table = $wpdb->prefix . 'tc_prospecting_planets';

        $isInt = is_int($identifier);
        $sql   = $isInt
            ? "SELECT * FROM $table WHERE id=%d"
            : "SELECT * FROM $table WHERE name=%s";
        $row   = $wpdb->get_row($wpdb->prepare($sql, $identifier));

        if ($row) {
            $planet = new self($row->id, $row->name, $row->system, $row->sector, $row->location, (int)$row->size);
            $planet->loadGrids();
            return $planet;
        }
        return null;
    }

    public function saveToDatabase(): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'tc_prospecting_planets';

        $original = null;
        if ($this->id > 0) {
            $original = self::load($this->id);
        }

        $data = [
            'name' => $this->name,
            'system' => $this->system,
            'sector' => $this->sector,
            'location' => $this->location,
            'size' => $this->size
        ];

        if ($this->id > 0) {
            $res = $wpdb->update($table, $data, ['id' => $this->id]);
            if ($res === false) {
                return false;
            }
            if ($original) {
                $changeFields = [
                    'name'     => 'Name',
                    'system'   => 'System',
                    'sector'   => 'Sector',
                    'location' => 'Location',
                    'size'     => 'Size'
                ];
                $changes = ProspectingLogger::compareChanges($original, $this, $changeFields);
                if (!empty($changes)) {
                    $msg = "Modified planet {$this->name}: " . implode('; ', $changes);
                    ProspectingLogger::logEvent([
                        'object_type' => 'planet',
                        'object_id'   => $this->id,
                        'planet_id'   => $this->id,
                        'event_type'  => 'MODIFIED_PLANET',
                        'event'       => $msg
                    ]);
                }
            }
            return true;
        } else {
            $insert = $wpdb->insert($table, $data);
            if ($insert) {
                $this->id = $wpdb->insert_id;
                $msg = "Created planet {$this->name}";
                ProspectingLogger::logEvent([
                    'object_type' => 'planet',
                    'object_id'   => $this->id,
                    'planet_id'   => $this->id,
                    'event_type'  => 'CREATED_PLANET',
                    'event'       => $msg
                ]);
                return true;
            }
            return false;
        }
    }

    public static function delete(int $id): ?bool {
        global $wpdb;
        $planet = self::load($id);
        if (!$planet) {
            return null;
        }
        $msg = "Deleted planet {$planet->getName()}";
        ProspectingLogger::logEvent([
            'object_type' => 'planet',
            'object_id'   => $planet->id,
            'planet_id'   => $planet->id,
            'event_type'  => 'DELETED_PLANET',
            'event'       => $msg
        ]);
        return (bool) $wpdb->delete($wpdb->prefix . 'tc_prospecting_planets', ['id' => $id], ['%d']);
    }

    public function loadGrids(): void {
        $this->grids = [];
        for ($x = 0; $x < $this->size; $x++) {
            for ($y = 0; $y < $this->size; $y++) {
                $g = new Grid($this->id, $x, $y);
                $g->loadFromDatabase();
                $this->grids[] = $g;
            }
        }
    }

    public function getAllDeposits(): array {
        return Deposit::getDepositsByPlanet($this->id);
    }

    public function addGrid(int $x, int $y, string $terrainType): Grid {
        $g = new Grid($this->id, $x, $y, $terrainType);
        $g->saveToDatabase();
        $this->grids[] = $g;
        return $g;
    }

    public function getTerrainData(): array {
        $map = [];
        foreach ($this->grids as $g) {
            $map["{$g->getX()}_{$g->getY()}"] = $g->getTerrainType();
        }
        return $map;
    }

    public function getDepositData(): array {
        $data = [];
        foreach ($this->getAllDeposits() as $dep) {
            $key = "{$dep->getX()}_{$dep->getY()}";
            $data[$key] = [
                'id'                => $dep->getId(),
                'type'              => $dep->getDepositType(),
                'size'              => $dep->getSize(),
                'prospector'        => $dep->getProspector(),
                'prospecting_time'  => $dep->getProspectingTime()?->format('Y-m-d H:i:s'),
                'last_updated'      => $dep->getLastUpdated()?->format('Y-m-d H:i:s')
            ];
        }
        return $data;
    }

    public function getId(): int          { return $this->id; }
    public function getName(): string     { return $this->name; }
    public function setName(string $n): void { $this->name = $n; }
    public function getSystem(): string   { return $this->system; }
    public function setSystem(string $s): void { $this->system = $s; }
    public function getSector(): string   { return $this->sector; }
    public function setSector(string $s): void { $this->sector = $s; }
    public function getLocation(): string { return $this->location; }
    public function setLocation(string $l): void { $this->location = $l; }
    public function getSize(): int        { return $this->size; }
    public function setSize(int $sz): void{ $this->size = $sz; }
    public function getGrids(): array     { return $this->grids; }
}
