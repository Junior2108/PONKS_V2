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
