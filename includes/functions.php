<?php
// =====================================================================
// Petites fonctions utilisées partout sur le site
// =====================================================================

// "e" comme "escape" (échapper).
// À utiliser pour CHAQUE donnée affichée dans du HTML : protège contre le XSS.
// Exemple dans une page :  echo e($work['title']);
// (attention : écrire le symbole de fermeture PHP, même dans un commentaire //,
//  termine le bloc PHP - c'est le bug qu'on a eu ici !)
function e(?string $text): string
{
    // ?? '' : si $text vaut null (colonne vide en base), on affiche une chaîne vide
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

// Fabrique un slug (identifiant pour l'URL) à partir d'un titre :
//   "Le Tiers Instruit" -> "le_tiers_instruit"
//   "FLÂNEUR"           -> "flaneur"
function slugify(string $text): string
{
    // Supprime les accents : Â -> A, Œ -> OE, é -> e
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = strtolower($text);
    // Tout ce qui n'est pas lettre ou chiffre devient "_"
    // (+ : une suite de plusieurs caractères ne donne qu'un seul "_")
    $text = preg_replace('/[^a-z0-9]+/', '_', $text);

    return trim($text, '_');
}
