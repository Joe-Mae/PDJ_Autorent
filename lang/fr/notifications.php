<?php

return [
    'phone' => [
        'code' => 'Votre code PDJ Autorent est : :code. Il est valable :minutes minutes.',
    ],

    'booking' => [
        'received' => "Bonjour :name,\n\nVotre réservation :reference est enregistrée, en attente de vérification.\n\n:vehicle\nDu :debut au :fin\n:a_emporter à payer à l'agence\n\nN'oubliez pas :permis.\n\nPDJ Autorent, Lomé",
        'confirmed' => "Réservation confirmée 👍\n\n:vehicle\nRetrait : :debut\nRetour : :fin\n\nÀ apporter : vos papiers originaux et :a_emporter.\n\nToute annulation jusqu'au :annulation est gratuite.\nPDJ Autorent — :agence",
        'refused' => "Bonjour :name,\n\nVotre réservation :reference n'a pas pu être confirmée.\nMotif : :motif\n\nAppelez-nous au :agence, on va trouver une solution.\nPDJ Autorent",
        'cancelled' => "Votre réservation :reference est annulée. Aucun frais.\n\nPDJ Autorent — :agence",
        'reminder' => "PDJ Autorent\n\nRappel : votre :vehicle est prête demain :demain.\n\nSi tout va bien, tapez juste sur le bouton ci-dessous :\n:lien\n\nMerci d'arriver à l'heure. L'agence est ouverte de 8h à 19h.",
        'no_show' => "Bonjour :name,\n\nNous vous avons attendu ce matin pour :reference, sans succès. La voiture a été remise en location.\n\nSi c'est un oubli, dites-le nous : on peut reprogrammer.\nPDJ Autorent — :agence",
    ],
];
