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

    'verification_status' => [
        'non_requis' => 'Not required',
        'en_attente' => 'Pending',
        'conforme' => 'Compliant',
        'non_conforme' => 'Non-compliant',
    ],

    'uploaded' => ':type uploaded. The original documents will be checked at the agency before vehicle handover.',
    'unreadable' => 'This image could not be read. Try a sharper photo or a PDF.',
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
    'privacy_purge' => 'They are deleted :months months after your rental, or whenever you ask.',
];
