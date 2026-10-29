<?php

return [
    'phone' => [
        'code' => 'Votre code :company est : :code. Il est valable :minutes minutes.',
    ],

    'booking' => [
        'received' => "Bonjour :name,\n\nVotre réservation :reference est enregistrée, en attente de vérification.\n\n:vehicle\nDu :debut au :fin\n:a_emporter à payer à l'agence\n\nN'oubliez pas :permis.\n\n:company, Lomé",
        'confirmed' => "Réservation confirmée 👍\n\n:vehicle\nRetrait : :debut\nRetour : :fin\n\nDocuments à présenter : :documents\nMontant à prévoir : :a_emporter.\n\nToute annulation jusqu'au :annulation est gratuite.\n:company — :agence",
        'refused' => "Bonjour :name,\n\nVotre réservation :reference n'a pas pu être confirmée.\nMotif : :motif\n\nAppelez-nous au :agence, on va trouver une solution.\n:company",
        'cancelled' => "Votre réservation :reference est annulée. Aucun frais.\n\n:company — :agence",
        'reminder' => ":company\n\nRappel : votre :vehicle est prête demain :demain.\n\nSi tout va bien, tapez juste sur le bouton ci-dessous :\n:lien\n\nMerci d'arriver à l'heure. L'agence est ouverte de 8h à 19h.",
        'no_show' => "Bonjour :name,\n\nNous vous avons attendu ce matin pour :reference, sans succès. La voiture a été remise en location.\n\nSi c'est un oubli, dites-le nous : on peut reprogrammer.\n:company — :agence",
    ],

    'mail' => [
        'title' => 'Réservation :reference',
        'cta' => 'Voir ma réservation',
        'no_license' => 'Bonne nouvelle : avec chauffeur, pas besoin de permis — présentez juste votre pièce d\'identité.',
        'agency_title' => 'Nouvelle demande :reference',
        'agency_cta' => 'Ouvrir dans l\'administration',
        'with_driver' => 'Avec chauffeur',
        'without_driver' => 'Sans chauffeur',
    ],

    'subjects' => [
        'received' => 'Réservation :reference reçue — :company',
        'confirmed' => 'Réservation :reference confirmée 👍 — :company',
        'refused' => 'Réservation :reference non confirmée — :company',
        'cancelled' => 'Réservation :reference annulée — :company',
        'new_request' => 'Nouvelle demande de réservation :reference — :company',
        'new_driver_only_request' => 'Nouvelle demande de chauffeur :reference — :company',
    ],

    'intros' => [
        'received' => 'Bonjour, votre réservation :reference est enregistrée, en attente de vérification.',
        'confirmed' => 'Bonne nouvelle : votre réservation :reference est confirmée.',
        'refused' => 'Votre réservation :reference n\'a malheureusement pas pu être confirmée.',
        'cancelled' => 'Votre réservation :reference est annulée. Aucun frais.',
        'new_request' => 'Une nouvelle demande de réservation :reference attend une confirmation.',
        'new_driver_only_request' => 'Une nouvelle demande de chauffeur seul :reference attend une confirmation.',
    ],

    'lines' => [
        'reference' => 'Référence',
        'vehicle' => 'Véhicule',
        'period' => 'Période',
        'to_pay' => 'À payer à l\'agence',
        'deposit' => 'Caution',
        'driver' => 'Avec chauffeur',
        'reason' => 'Motif',
        'client' => 'Client',
        'phone' => 'Téléphone',
        'email' => 'E-mail',
        'service' => 'Prestation',
        'driver_option' => 'Chauffeur',
    ],

    'footers' => [
        'agency' => ':company — appelez-nous au :phone, on va trouver une solution.',
        'received' => ':company — appelez-nous au :phone si quelque chose n\'est pas clair.',
        'confirmed' => ':company — à bientôt.',
        'refused' => ':company — appelez-nous au :phone, on va trouver une solution.',
        'cancelled' => ':company — n\'hésitez pas à réserver à nouveau.',
        'agency_internal' => 'Notification interne — :company.',
    ],
];
