<?php
class ProspectingLogger {
    public static function logEvent(array $fields): void {
        global $wpdb;

        if (!isset($fields['event_type'], $fields['event'])) {
            return;
        }
        $defaults = [
            'object_type' => '',
            'object_id'   => 0,
            'planet_id'   => 0,
            'user'        => wp_get_current_user()->user_login ?? 'Unknown',
            'datetime'    => current_time('mysql')
        ];
        $fields = array_merge($defaults, $fields);

        $wpdb->insert(
            "{$wpdb->prefix}tc_prospecting_changelog",
            $fields,
            ['%s','%d','%d','%s','%s','%s','%s']
        );
    }

    public static function compareChanges(object $oldObj, object $newObj, array $fields): array {
        $notes = [];
        foreach ($fields as $property => $label) {
            // Build getter method name. e.g., "name" becomes "getName"
            $getter = 'get' . ucfirst($property);
            if (method_exists($oldObj, $getter) && method_exists($newObj, $getter)) {
                $oldVal = $oldObj->{$getter}();
                $newVal = $newObj->{$getter}();
            } else {
                // Fallback to direct access (shouldn't happen)
                $oldVal = $oldObj->$property;
                $newVal = $newObj->$property;
            }
            
            if ($oldVal !== $newVal) {
                $notes[] = "$label: {$oldVal} → {$newVal}";
            }
        }
        return $notes;
    }
}
