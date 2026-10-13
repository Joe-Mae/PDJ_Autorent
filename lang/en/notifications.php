<?php

return [
    'phone' => [
        'code' => 'Your PDJ Autorent code is :code. It is valid for :minutes minutes.',
    ],

    'booking' => [
        'received' => "Hi :name,\n\nYour booking :reference has been recorded and is awaiting review.\n\n:vehicle\nFrom :debut to :fin\n:a_emporter to pay at the agency\n\nDo not forget: :permis.\n\nPDJ Autorent, Lomé",
        'confirmed' => "Booking confirmed 👍\n\n:vehicle\nPickup: :debut\nReturn: :fin\n\nBring: your original documents and :a_emporter.\n\nFree cancellation until :annulation.\nPDJ Autorent — :agence",
        'refused' => "Hi :name,\n\nYour booking :reference could not be confirmed.\nReason: :motif\n\nCall us on :agence, we will find a solution.\nPDJ Autorent",
        'cancelled' => "Your booking :reference is cancelled. No charge.\n\nPDJ Autorent — :agence",
        'reminder' => "PDJ Autorent\n\nReminder: your :vehicle is ready tomorrow at :demain.\n\nIf all is well, just tap the button below:\n:lien\n\nPlease arrive on time. The agency is open 8am to 7pm.",
        'no_show' => "Hi :name,\n\nWe waited for you this morning for :reference, with no luck. The car went out on another rental.\n\nIf it was an oversight, tell us: we can reschedule.\nPDJ Autorent — :agence",
    ],
];
