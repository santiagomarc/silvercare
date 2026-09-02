<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Capture Session Retention
    |--------------------------------------------------------------------------
    |
    | A capture session holds an uploaded photograph of a prescription label or
    | a home vitals monitor — protected health information. It exists only long
    | enough for the patient to confirm what was read from it; after that the
    | confirmed value lives on the medication or health metric record and the
    | image has no further purpose.
    |
    | `captures:purge-expired` deletes both the row and the stored file.
    |
    */
    'retention_hours' => 24,

    // Keep a confirmed session's metadata a little longer for the caregiver's
    // audit trail, but delete the image itself on the same schedule.
    'confirmed_metadata_retention_days' => 30,
];
