<?php

return [

    'max_gps_accuracy_meter' => (float) env('ATTENDANCE_MAX_GPS_ACCURACY_METER', 50),
    'max_location_age_seconds' => (int) env('ATTENDANCE_MAX_LOCATION_AGE_SECONDS', 30),
    'location_timeout_ms' => (int) env('ATTENDANCE_LOCATION_TIMEOUT_MS', 15000),
    'rate_limit_per_minute' => (int) env('ATTENDANCE_RATE_LIMIT_PER_MINUTE', 30),

    'check_in_deadline' => env('ATTENDANCE_CHECK_IN_DEADLINE', '09:00'),
    'work_start' => env('ATTENDANCE_WORK_START', '09:00'),
    'work_duration_hours' => (int) env('ATTENDANCE_WORK_DURATION_HOURS', 9),
    'work_end' => env('ATTENDANCE_WORK_END', '18:00'),

    'map' => [
        'center_lat' => (float) env('DEFAULT_MAP_CENTER_LAT', -6.200100),
        'center_lng' => (float) env('DEFAULT_MAP_CENTER_LNG', 106.816700),
        'zoom' => (int) env('DEFAULT_MAP_ZOOM', 16),
    ],

];
