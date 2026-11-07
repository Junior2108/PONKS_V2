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

// Adresse publique d'une œuvre : "flaneur" -> "flaneur.html"
// Le fichier n'existe pas : le .htaccess redirige EN INTERNE vers oeuvre.php?slug=flaneur.
// Les anciennes adresses du site restent donc valables.
function work_url(string $slug): string
{
    return rawurlencode($slug) . '.html';
}

// Transforme un texte saisi dans l'admin en HTML sûr :
//   - une ligne vide sépare deux paragraphes <p>
//   - un simple retour à la ligne devient <br>
//   - les adresses http(s)://... deviennent des liens cliquables
// $class : classe CSS facultative ajoutée à chaque <p>
function text_to_html(?string $text, string $class = ''): string
{
    $html = '';
    $classAttr = $class !== '' ? ' class="' . e($class) . '"' : '';

    // \R\s*\R : un retour à la ligne, des espaces éventuels, un autre retour = ligne vide
    foreach (preg_split('/\R\s*\R/', trim($text ?? '')) as $paragraph) {
        if (trim($paragraph) === '') {
            continue;
        }
        // D'ABORD on échappe (le texte ne peut plus contenir de HTML),
        // ENSUITE on ajoute nos propres balises <br> et <a>
        $html .= "<p$classAttr>" . linkify(nl2br(e(trim($paragraph)))) . "</p>\n";
    }

    return $html;
}

// Rend cliquables les adresses http(s)://... d'un texte DÉJÀ échappé avec e()
function linkify(string $safeHtml): string
{
    return preg_replace('#https?://[^\s<]+#', '<a href="$0" target="_blank" rel="noopener noreferrer">$0</a>', $safeHtml);
}

// Coupe le champ "infos" en lignes (une ligne = un <p> sur le site)
function info_lines(?string $info): array
{
    return array_filter(array_map('trim', preg_split('/\R/', $info ?? '')));
}

// Extrait l'identifiant d'une vidéo YouTube, quel que soit le format du lien :
//   https://www.youtube.com/watch?v=ID   https://youtu.be/ID   https://www.youtube.com/embed/ID
// Renvoie null si ce n'est pas un lien YouTube reconnu.
function youtube_id(?string $url): ?string
{
    if ($url && preg_match('#(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})#', $url, $m)) {
        return $m[1];
    }

    return null;
}
