<?php

/**
 * Quel sens de conversion rend « é » ?
 *
 * Le fichier contient « FranÃ§ais » : la chaîne, une fois lue comme de l'UTF-8,
 * vaut « FranÃ§ais ». Les octets derrière « Ã » et « § » sont ceux du
 * Latin-1 relu puis réécrit. Retrouver « Français » demande de refaire le chemin
 * inverse, et le sens de l'appel change tout : appliqué à l'envers, il aggrave
 * la casse (« FranÃÂ§ais »). On teste donc avant d'écrire quoi que ce soit.
 */
$cas = [
    'Français' => 'Fran'."\xC3\x83\xC2\xA7".'ais',
    'disponibilité' => 'disponibilit'."\xC3\x83\xC2\xA9",
    'Paramètres' => 'Param'."\xC3\x83\xC2\xA8".'tres',
    'contrôle' => 'Contr'."\xC3\x83\xC2\xB4".'le',
    'âge' => "\xC3\x83\xC2\xA0".'ge',
];

$sens = [
    'Win1252' => fn (string $s): string => mb_convert_encoding($s, 'Windows-1252', 'UTF-8'),
    'Latin1' => fn (string $s): string => mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8'),
    'UTF8-in' => fn (string $s): string => mb_convert_encoding($s, 'UTF-8', 'ISO-8859-1'),
];

printf('%-15s %-12s %-12s %-12s %-12s%s', 'attendu', 'actuel', 'Win1252', 'Latin1', 'UTF8-in', PHP_EOL);

foreach ($cas as $attendu => $mojibake) {
    $ligne = [];

    foreach ($sens as $nom => $convertir) {
        $resultat = $convertir($mojibake);
        $ligne[$nom] = mb_check_encoding($resultat, 'UTF-8')
            ? ($resultat === $attendu ? 'OK  ' : ' faux')
            : ' non-utf8';
    }

    printf(
        '%-15s %-12s %-12s %-12s %-12s%s',
        $attendu,
        $mojibake,
        $ligne['Win1252'],
        $ligne['Latin1'],
        $ligne['UTF8-in'],
        PHP_EOL,
    );
}
