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
];
