<?php

return [
    'status' => [
        'en_attente' => 'Awaiting approval',
        'confirmee' => 'Confirmed',
        'en_cours' => 'Ongoing',
        'terminee' => 'Completed',
        'annulee' => 'Cancelled',
        'refusee' => 'Declined',
        'non_presente' => 'No-show',
    ],

    'status_reason' => [
        'no_show' => 'Customer did not show up within the grace period',
    ],

    'block_reason' => [
        'maintenance' => 'Maintenance',
        'controle_technique' => 'Technical inspection',
        'accident' => 'Accident / repairs',
        'detente' => 'Scheduled downtime',
        'interne' => 'Internal use',
    ],

    'step' => [
        'summary' => 'Summary',
        'contact' => 'Driver',
        'verify' => 'Verification',
        'documents' => 'Documents',
        'review' => 'Confirmation',
    ],

    'line' => [
        'rental' => 'Rental: :days days',
        'driver' => 'Chauffeur: :blocks blocks of 4 h',
    ],

    'error' => [
        'vehicle_not_bookable' => 'This vehicle is not available for rent at the moment.',
        'vehicle_just_taken' => 'This car has just been booked for those dates. Here are alternatives.',
        'vehicle_in_maintenance' => 'This vehicle is in the workshop for that period.',
        'no_driver_available' => 'No chauffeur available for those dates.',
        'account_blocked' => 'Your account is blocked after several no-shows. Call the agency on :phone to unblock it.',
        'too_many_bookings' => 'You already have :max booking(s) in progress. Cancel one or contact the agency.',
        'too_young' => 'You must be :age years old to rent a vehicle.',
        'invalid_transition' => 'Cannot move from “:from” to “:to”.',
        'cannot_cancel' => 'This booking is “:status”: it can no longer be cancelled online.',
        'documents_missing' => 'A document is missing. Go back to the previous step to upload it.',
        'outside_opening_hours' => 'The agency is open from :open to :close. Please pick a time within that range.',
        'code_expired' => 'The code has expired. Request a new one.',
        'code_invalid' => 'Wrong code. :left attempt(s) left.',
        'code_sent' => 'Code sent to :phone.',
        'no_account' => 'Your account could not be found. Please redo the previous step.',
        'min_license' => 'Your licence must be at least :years year(s) old.',
    ],

    'cancelled_ok' => 'Booking cancelled. No charge.',

    'summary_title' => 'Your booking',
    'cost_summary' => 'Cost summary',
    'change_vehicle' => 'Change car',
    'update_price' => 'Update the price',
    'continue' => 'Continue',

    'contact_title' => 'Who is driving?',
    'contact_intro' => 'The name must match your ID card and driving licence exactly.',
    'field_first_name' => 'First name',
    'field_last_name' => 'Last name',
    'field_birth_date' => 'Date of birth',
    'field_birth_date_hint' => 'You must be :age or older. Age is checked against your ID card.',
    'field_phone' => 'Phone',
    'field_phone_hint' => 'This is how we send your confirmation and the day-before reminder.',
    'field_email' => 'Email',
    'field_email_hint' => 'Useful if you want a written receipt. Optional.',
    'field_password' => 'Password',
    'field_password_hint' => '8 characters minimum. You can set it later too.',
    'field_password_confirm' => 'Confirm password',
    'password_intro' => 'No account yet? Choose a password to follow your bookings.',
    'optional' => 'optional',

    'verify_title' => 'Let us check your number',
    'verify_intro' => 'A 6-digit code was just sent to :phone.',
    'verify_cta' => 'Verify my number',
    'verify_why' => 'We verify the number because that is how you receive your confirmation, your reminder and your booking receipt. Without a verified number, the reminder does not go out.',
    'code_digit' => 'Digit :number',
    'code_sent' => 'Code sent again to :phone.',
    'resend_whatsapp' => 'Resend on WhatsApp',
    'resend_sms' => 'Resend by SMS',
    'phone_verified' => 'Verified number',

    'documents_title' => 'Your documents',
    'documents_intro' => 'A photo or a scan, just to save time at the counter. Originals are still required.',
    'missing_documents' => 'Still missing:',
    'back_to_documents' => 'Back to documents',

    'review_title' => 'Let us double-check',
    'review_intro' => 'One last look before you send it.',
    'to_pay' => 'to pay at the agency',

    'confirm_cta' => 'Confirm my booking',
    'confirm_note' => 'No payment now. An agent checks your documents and confirms within 2 hours.',

    'confirmation_title' => 'Your booking is recorded',
    'confirmation_meta' => 'Booking recorded — PDJ Autorent Lomé',
    'confirmation_intro' => 'An agent is checking your documents. You will know within 2 hours if it is confirmed.',
    'reference' => 'Reference',
    'download_receipt' => 'Download the booking receipt',
    'what_happens_next' => 'What happens next',
    'next_steps' => 'You get an SMS and a WhatsApp message as soon as the agent has checked your papers. The day before, a reminder asks you to confirm you are coming. On pickup morning, you show your originals and pay at the agency.',
    'bring_with_you' => 'To show at pickup',
    'original' => '(original)',
    'amount_breakdown' => 'rental + deposit',
    'agency' => 'The agency',
    'open_maps' => 'Open in Google Maps',

    'presence_confirmed' => 'Thanks, your attendance is recorded. See you soon!',

    'reminder' => [
        'license_needed' => 'do not forget your driving licence',
        'no_license_needed' => 'with a chauffeur, no licence needed, just bring your ID',
    ],

    'driver_blocks' => 'blocks of :hours h',
    'cancel_confirm' => 'Cancel this booking? Cancellation is free, you will not be charged.',
    'cancel_free' => 'Cancel free of charge',
];
