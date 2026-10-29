<?php

return [
    'phone' => [
        'code' => 'Your :company code is :code. It is valid for :minutes minutes.',
    ],

    'booking' => [
        'received' => "Hi :name,\n\nYour booking :reference has been recorded and is awaiting review.\n\n:vehicle\nFrom :debut to :fin\n:a_emporter to pay at the agency\n\nDo not forget: :permis.\n\n:company, Lomé",
        'confirmed' => "Booking confirmed 👍\n\n:vehicle\nPickup: :debut\nReturn: :fin\n\nDocuments to bring: :documents\nAmount due: :a_emporter.\n\nFree cancellation until :annulation.\n:company — :agence",
        'refused' => "Hi :name,\n\nYour booking :reference could not be confirmed.\nReason: :motif\n\nCall us on :agence, we will find a solution.\n:company",
        'cancelled' => "Your booking :reference is cancelled. No charge.\n\n:company — :agence",
        'reminder' => ":company\n\nReminder: your :vehicle is ready tomorrow at :demain.\n\nIf all is well, just tap the button below:\n:lien\n\nPlease arrive on time. The agency is open 8am to 7pm.",
        'no_show' => "Hi :name,\n\nWe waited for you this morning for :reference, with no luck. The car went out on another rental.\n\nIf it was an oversight, tell us: we can reschedule.\n:company — :agence",
    ],

    'mail' => [
        'title' => 'Booking :reference',
        'cta' => 'View my booking',
        'no_license' => 'Good news: with a chauffeur, no licence is needed — just bring your ID.',
        'agency_title' => 'New request :reference',
        'agency_cta' => 'Open in the back office',
        'with_driver' => 'With a chauffeur',
        'without_driver' => 'Without a chauffeur',
    ],

    'subjects' => [
        'received' => 'Booking :reference received — :company',
        'confirmed' => 'Booking :reference confirmed 👍 — :company',
        'refused' => 'Booking :reference could not be confirmed — :company',
        'cancelled' => 'Booking :reference cancelled — :company',
        'new_request' => 'New booking request :reference — :company',
        'new_driver_only_request' => 'New chauffeur request :reference — :company',
    ],

    'intros' => [
        'received' => 'Hi, your booking :reference has been recorded and is awaiting review.',
        'confirmed' => 'Good news: your booking :reference is confirmed.',
        'refused' => 'Unfortunately your booking :reference could not be confirmed.',
        'cancelled' => 'Your booking :reference has been cancelled. No charge.',
        'new_request' => 'A new booking request :reference is waiting for confirmation.',
        'new_driver_only_request' => 'A new chauffeur-only request :reference is waiting for confirmation.',
    ],

    'lines' => [
        'reference' => 'Reference',
        'vehicle' => 'Vehicle',
        'period' => 'Period',
        'to_pay' => 'Amount to pay at the agency',
        'deposit' => 'Deposit',
        'driver' => 'With a chauffeur',
        'reason' => 'Reason',
        'client' => 'Customer',
        'phone' => 'Phone',
        'email' => 'Email',
        'service' => 'Service',
        'driver_option' => 'Chauffeur',
    ],

    'footers' => [
        'agency' => ':company — call us on :phone, we will find a solution.',
        'received' => ':company — call us on :phone if anything is unclear.',
        'confirmed' => ':company — see you soon.',
        'refused' => ':company — call us on :phone, we will find a solution.',
        'cancelled' => ':company — feel free to book again.',
        'agency_internal' => 'Internal notification — :company.',
    ],
];
