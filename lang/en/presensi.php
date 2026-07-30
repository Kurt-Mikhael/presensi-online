<?php

return [

    'errors' => [
        'occurred'             => 'An error occurred. Please try again.',
        'OFFLINE'              => 'Attendance requires an internet connection. Please check your connection.',
        'LOCATION_PERMISSION_DENIED' => 'Location permission denied. Enable location permission to take attendance.',
        'LOCATION_UNAVAILABLE' => 'Location could not be determined. Make sure GPS is on and try again.',
        'LOCATION_STALE'       => 'Location data is stale. Please capture a fresh location.',
        'LOW_ACCURACY'         => 'GPS accuracy is too low. Try a more open location.',
        'OUTSIDE_AREA'         => 'You are outside the attendance area.',
        'DUPLICATE_CHECK_IN'   => 'You have already checked in today.',
        'DUPLICATE_CHECK_OUT'  => 'You have already checked out today.',
        'CHECK_IN_REQUIRED'    => 'Please check in before checking out.',
        'LOCATION_NOT_CONFIGURED' => 'Attendance area is not yet configured by admin.',
        'INVALID_LOCATION'     => 'Invalid location data.',
        'PHOTO_REQUIRED'       => 'A check-in photo is required.',
        'PHOTO_PERMISSION_DENIED' => 'Camera permission denied. Enable the camera to continue.',
        'PHOTO_CAPTURE_FAILED'  => 'Check-in photo could not be taken. Please try again.',
        'PHOTO_UNSUPPORTED'    => 'This device does not support the required camera access.',
    ],

    'success' => [
        'check_in'  => 'Check-in successful.',
        'check_out' => 'Check-out successful.',
    ],

];