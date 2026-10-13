<?php

return [
    'type' => [
        'piece_identite' => 'ID card',
        'permis' => 'Driving licence',
        'selfie' => 'Selfie with ID',
        'contrat' => 'Rental agreement',
        'photo_vehicule' => 'Vehicle photo',
    ],

    'status' => [
        'en_attente' => 'Awaiting review',
        'conforme' => 'Approved',
        'non_conforme' => 'Rejected',
        'a_relever' => 'To review later',
        'presente' => 'Shown at the agency',
        'expire' => 'Expired',
    ],

    'uploaded' => ':type uploaded. An agent checks it before confirming your booking.',
    'deleted' => 'Document deleted.',
    'delete_confirm' => 'Delete this document and upload another one?',
    'replace' => 'Replace',
    'use_this_one' => 'Reuse this document',
    'upload' => 'Upload',

    'upload_hint' => 'JPG, PNG or PDF — 5 MB maximum.',
    'upload_cta' => 'Take a photo or choose a file',
    'upload_reuse' => 'Already verified on :date, we will reuse this document.',
    'upload_replace' => 'Replace with a newer one',
    'upload_camera' => 'Take a photo',

    'selfie_title' => 'Add a selfie with your ID (optional)',
    'selfie_hint' => 'It helps us confirm the document is really yours. Not required.',
    'take_selfie' => 'Take a selfie',

    'privacy_title' => 'Your documents, in plain terms',
    'privacy_storage' => 'Your files are stored outside the website, not reachable by a direct link.',
    'privacy_access' => 'Only our agents can open them, through a link valid for 15 minutes.',
    'privacy_purge' => 'They are deleted 12 months after your rental, or whenever you ask.',
];
