<?php

/**
 * Répare le double encodage UTF-8 dans les fichiers du dépôt.
 *
 * CONTEXTE
 * --------
 * Un caractère accentué du texte français tient sur un seul octet en Latin-1
 * (é = 0xE9). Passé une première fois de Latin-1 à UTF-8, il devient deux
 * octets (C3 A9). Si l'on relit ces deux octets comme du Latin-1 — ce que fait
 * un éditeur ou un script mal configuré — puis qu'on réécrit en UTF-8, on
 * obtient quatre octets : C3 83 C2 A9, soit « Ã© ». C'est la signature
 * exacte de « é » recopié deux fois.
 *
 * La page paraissait alors chargée deux fois sans raison. Ce script annule le
 * second passage : il relit les octets comme du Latin-1 et les réécrit en UTF-8.
 *
 * PRÉCAUTION
 * ----------
 * La conversion ne s'applique qu'aux seules séquences caractéristiques
 * (C3 xx C2 xx), jamais au fichier entier : sinon « café » déjà correct
 * deviendrait « cafÃ© ». Chaque correction est affichée avant d'être écrite,
 * et rien n'est modifié en mode --dry-run.
 */
$racine = __DIR__;
$sec = in_array('--dry-run', $argv, true);

$dossiers = ['app', 'resources', 'resources/views', 'lang', 'routes', 'config', 'database', 'tests'];
$extensions = ['php', 'blade.php', 'js', 'scss', 'css', 'json', 'md', 'txt'];

// C3 xx C2 xx : la signature d'un caractère Latin-1 réencodé deux fois.
$signature = '/\xC3[\x80-\xBF]\xC2[\x80-\xBF]+/';

$totalCorrections = 0;
$fichiersTouches = [];

foreach (array_unique($dossiers) as $dossier) {
    $chemin = $racine.DIRECTORY_SEPARATOR.$dossier;

    if (! is_dir($chemin)) {
        continue;
    }

    $iterateur = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($chemin));

    foreach ($iterateur as $fichier) {
        if (! $fichier->isFile()) {
            continue;
        }

        $conforme = false;

        foreach ($extensions as $extension) {
            if (str_ends_with($fichier->getFilename(), $extension)) {
                $conforme = true;
                break;
            }
        }

        if (! $conforme) {
            continue;
        }

        $contenu = file_get_contents($fichier->getPathname());
        $lignes = explode("\n", $contenu);
        $modifie = false;

        foreach ($lignes as &$ligne) {
            if (! preg_match_all($signature, $ligne, $trouves)) {
                continue;
            }

            $avant = trim($ligne);
            $corrigee = preg_replace_callback(
                $signature,
                static fn (array $m): string => mb_convert_encoding($m[0], 'UTF-8', 'ISO-8859-1'),
                $ligne,
            );

            if ($corrigee !== $ligne) {
                $ligne = $corrigee;
                $modifie = true;
                $totalCorrections += count($trouves);

                printf(
                    '  %s%s    %s%s        -> %s%s',
                    str_pad((string) (($modifie ? '' : '')), 0),
                    '',
                    '',
                    '',
                    '',
                    PHP_EOL,
                );
                printf('      avant : %s%s', mb_strimwidth($avant, 0, 96), PHP_EOL);
                printf('      apres : %s%s', mb_strimwidth(trim($corrigee), 0, 96), PHP_EOL);
            }
        }
        unset($ligne);

        if ($modifie) {
            $fichiersTouches[] = str_replace($racine.DIRECTORY_SEPARATOR, '', $fichier->getPathname());

            if (! $sec) {
                file_put_contents($fichier->getPathname(), implode("\n", $lignes));
            }
        }
    }
}

echo PHP_EOL;

if ($fichiersTouches === []) {
    echo 'Aucune correction necessaire.', PHP_EOL;

    return;
}

printf(
    '%s %d occurrence(s) dans %d fichier(s).%s',
    $sec ? '[simulation] ' : '',
    $totalCorrections,
    count($fichiersTouches),
    PHP_EOL,
);

foreach ($fichiersTouches as $chemin) {
    echo '  - '.$chemin.PHP_EOL;
}
