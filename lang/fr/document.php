<?php

return [
    'type' => [
        'piece_identite' => 'Pièce d\'identité',
        'permis' => 'Permis de conduire',
        'selfie' => 'Selfie avec la pièce',
        'contrat' => 'Contrat de location',
        'photo_vehicule' => 'Photo du véhicule',
    ],

    'status' => [
        'en_attente' => 'En attente de vérification',
        'conforme' => 'Conforme',
        'non_conforme' => 'Non conforme',
        'a_relever' => 'À revoir',
        'presente' => 'Présentée à l\'agence',
        'expire' => 'Expiré',
    ],

    'verification_status' => [
        'non_requis' => 'Non requis',
        'en_attente' => 'En attente',
        'conforme' => 'Conforme',
        'non_conforme' => 'Non conforme',
    ],

    'uploaded' => ':type envoyé. Les originaux seront vérifiés à l’agence avant la remise du véhicule.',
    'unreadable' => 'Ce fichier image n\'a pas pu être lu. Réessayez avec une photo plus nette ou un PDF.',
    'deleted' => 'Document supprimé.',
    'delete_confirm' => 'Supprimer ce document et en envoyer un autre ?',
    'replace' => 'Remplacer',
    'use_this_one' => 'Réutiliser ce document',
    'upload' => 'Envoyer',

    'upload_hint' => 'JPG, PNG ou PDF — 5 Mo maximum.',
    'upload_cta' => 'Prendre une photo ou choisir un fichier',
    'upload_reuse' => 'Déjà vérifié le :date, on réutilise ce document.',
    'upload_replace' => 'Remplacer par un plus récent',
    'upload_camera' => 'Prendre une photo',

    'selfie_title' => 'Ajoutez un selfie avec votre pièce (facultatif)',
    'selfie_hint' => 'Ça nous aide à confirmer que le papier est bien le vôtre. Ce n\'est pas obligatoire.',
    'take_selfie' => 'Faire un selfie',

    'privacy_title' => 'Vos documents, en clair',
    'privacy_storage' => 'Vos fichiers sont stockés hors du site, inaccessibles par un lien direct.',
    'privacy_access' => 'Seuls nos agents les consultent, via un lien valable 15 minutes.',
    'privacy_purge' => 'Ils sont supprimés :months mois après votre location, ou quand vous le demandez.',
];
