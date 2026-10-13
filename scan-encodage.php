<?php

/**
 * Cherche les traces de double encodage UTF-8 dans le code source.
 *
 * Un texte passé deux fois par un convertisseur UTF-8 -> Latin-1 -> UTF-8
 * devient illisible : « Français » finit en « FranÃ§ais ». Le symptôme est
 * reconnaissable sans ambiguïté : les octets C3 83 / C3 82 correspondent à « Ã »
 * et « Â », qui n'ont aucune raison d'apparaître seuls dans du texte français.
 *
 * Ce n'est PAS un diagnostic de rendu navigateur : il porte sur les fichiers du
 * dépôt, où une telle chaîne est définitivement perdue.
 */
$racine = __DIR__;
$dossiers = ['app', 'resources', 'lang', 'routes', 'config', 'database', 'tests'];
$extensions = ['php', 'blade.php', 'js', 'scss', 'css', 'json', 'md'];

// Motifs : octets caracteristiques d'un double encodage.
$signatures = [
    'A-majuscule accentuee (Ã)' => "\xC3\x83",
    'A-majuscule non accentuee (Â)' => "\xC3\x82",
    'c-tilde double (Ã§)' => "\xC3\x83\u{00A7}",
    'e-accent double (Ã©)' => "\xC3\x83\u{00C2}\u{00A9}",
];

$trouves = [];

foreach ($dossiers as $dossier) {
    $chemin = $racine.DIRECTORY_SEPARATOR.$dossier;

    if (! is_dir($chemin)) {
        continue;
    }

    $iterateur = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($chemin));

    foreach ($iterateur as $fichier) {
        if (! $fichier->isFile()) {
            continue;
        }

        $nom = $fichier->getFilename();
        $conforme = false;

        foreach ($extensions as $extension) {
            if (str_ends_with($nom, $extension)) {
                $conforme = true;
                break;
            }
        }

        if (! $conforme) {
            continue;
        }

        // Lecture binaire : on cherche des octets, pas des caractères.
        $contenu = file_get_contents($fichier->getPathname());

        foreach ($signatures as $nomSignature => $octets) {
            $position = strpos($contenu, $octets);

            if ($position === false) {
                continue;
            }

            // Contexte autour du coup.
            $debut = max(0, $position - 40);
            $extrait = substr($contenu, $debut, 100);
            $extrait = preg_replace('/\s+/', ' ', $extrait);

            $ligne = substr_count(substr($contenu, 0, $position), "\n") + 1;

            $trouves[] = [
                'fichier' => str_replace($racine.DIRECTORY_SEPARATOR, '', $fichier->getPathname()),
                'ligne' => $ligne,
                'signature' => $nomSignature,
                'extrait' => trim($extrait),
            ];
        }
    }
}

if ($trouves === []) {
    echo 'Aucun double encodage detecte dans le code source.', PHP_EOL;

    return;
}

echo count($trouves).' occurrence(s) :', PHP_EOL;

foreach ($trouves as $trouve) {
    printf(
        '  %s:%d  [%s]%s    %s%s',
        $trouve['fichier'],
        $trouve['ligne'],
        $trouve['signature'],
        PHP_EOL,
        '      ',
        $trouve['extrait'].PHP_EOL,
    );
}
