<?php
// =====================================================================
// IMPORT des anciennes pages HTML dans la base - en ligne de commande :
//
//   php database/import_html.php --dry-run   -> affiche ce qui serait importé, sans rien écrire
//   php database/import_html.php             -> importe pour de vrai
//
// Les œuvres dont le slug existe déjà en base sont IGNORÉES (on n'écrase
// jamais ce qui a été saisi dans l'admin). Pareil pour les pages de texte.
// Le script peut donc être relancé sans risque.
// =====================================================================

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

$dryRun = in_array('--dry-run', $argv, true);
$root   = dirname(__DIR__);   // dossier du site (parent de database/)

// ---------------------------------------------------------------------
// Corrections manuelles : erreurs de copier-coller dans les anciennes pages
// ---------------------------------------------------------------------
$overrides = [
    // La page dimensional_fruits.html affichait le titre "Le tiers-instruit"
    // et n'avait pas la vidéo (qui était sur films.html et index.html)
    'dimensional_fruits' => [
        'subtitle'  => null,
        'video_url' => 'https://www.youtube.com/watch?v=eSmiNT2IppE',
    ],
];

// ---------------------------------------------------------------------
// Outils de lecture du HTML
// ---------------------------------------------------------------------
// DOMDocument lit le HTML comme un navigateur : on peut ensuite chercher des
// éléments avec XPath, un langage de requête pour HTML/XML.
function load_html(string $file): DOMXPath
{
    $doc = new DOMDocument();
    // Le préfixe <?xml encoding> force la lecture en UTF-8 (sinon : FLÃ‚NEUR)
    // @ : masque les avertissements sur le HTML imparfait (balises mal fermées...)
    @$doc->loadHTML('<?xml encoding="UTF-8">' . file_get_contents($file));

    return new DOMXPath($doc);
}

// Texte d'un élément, avec <br> -> retour à la ligne et espaces nettoyés
function node_text(DOMNode $node): string
{
    $text = '';
    foreach ($node->childNodes as $child) {
        if ($child->nodeName === 'br') {
            $text .= "\n";
        } elseif ($child->nodeName === 'a' && $child->getAttribute('href') !== $child->textContent) {
            // Lien dont le texte n'est pas l'adresse : on garde le texte seul
            $text .= $child->textContent;
        } elseif ($child->nodeType === XML_TEXT_NODE) {
            // Comme un navigateur : dans le code source, retours à la ligne,
            // tabulations et espaces multiples ne valent qu'UN espace.
            // Seuls les <br> (ci-dessus) créent un vrai retour à la ligne.
            $text .= preg_replace('/\s+/u', ' ', $child->textContent);
        } else {
            $text .= node_text($child);
        }
    }
    // Nettoie le début et la fin de chaque ligne
    $lines = array_map(fn ($l) => trim(str_replace("\u{00A0}", ' ', $l)), explode("\n", $text));

    return trim(implode("\n", $lines));
}

// La classe CSS contient-elle ce mot ? (XPath n'a pas d'équivalent simple)
function has_class(string $class): string
{
    return "contains(concat(' ', normalize-space(@class), ' '), ' $class ')";
}

// Liste des liens de galerie d'une page de rubrique : ['flaneur' => ['title' => ..., 'image' => ...], ...]
function gallery_links(string $file): array
{
    $xp = load_html($file);
    $links = [];
    foreach ($xp->query('//div[' . has_class('gallery_block') . ']/a') as $a) {
        $slug = basename($a->getAttribute('href'), '.html');
        $title = $xp->query('.//div[' . has_class('gallery_block_title') . ']', $a)->item(0);
        $img = $xp->query('.//img', $a)->item(0);
        $links[$slug] = [
            'title' => $title ? node_text($title) : $slug,
            'image' => $img ? $img->getAttribute('src') : null,
        ];
    }

    return $links;
}

// ---------------------------------------------------------------------
// 1. Les listes : works.html donne la liste complète ET l'ordre
// ---------------------------------------------------------------------
$allWorks   = gallery_links("$root/works.html");
$publicArt  = gallery_links("$root/public_art.html");
$films      = gallery_links("$root/films.html");
$home       = gallery_links("$root/index.html");

// L'œuvre en grand en haut de l'accueil
$homeXp   = load_html("$root/index.html");
$heroLink = $homeXp->query('//div[' . has_class('image_head_index') . ']/a')->item(0);
$homeHero = $heroLink ? basename($heroLink->getAttribute('href'), '.html') : null;

// ---------------------------------------------------------------------
// 2. Lire chaque page d'œuvre
// ---------------------------------------------------------------------
$works = [];
$position = 0;

foreach ($allWorks as $slug => $listing) {
    $file = "$root/$slug.html";
    if (!is_file($file)) {
        echo "!! $slug.html introuvable, ignorée\n";
        continue;
    }

    $xp   = load_html($file);
    $text = $xp->query('//div[' . has_class('hero_work_text') . ']')->item(0);

    // Sous-titre : <span class="title_self"> dans le <h2>
    $span = $xp->query('.//h2/span[' . has_class('title_self') . ']', $text)->item(0);

    // Infos : les <p> de .hero_work_info, ou à défaut les <h3> (ancienne mise en page)
    $infoNodes = $xp->query('.//div[' . has_class('hero_work_info') . ']/p', $text);
    if ($infoNodes->length === 0) {
        $infoNodes = $xp->query('.//h3', $text);
    }
    $info = [];
    foreach ($infoNodes as $node) {
        $info[] = node_text($node);
    }

    // Accroche et texte : les <p> de la description (ou directement du bloc texte)
    $intro = '';
    $paragraphs = [];
    $descNodes = $xp->query('.//div[' . has_class('hero_work_description') . ']//p', $text);
    if ($descNodes->length === 0) {
        $descNodes = $xp->query('./p', $text);
    }
    foreach ($descNodes as $p) {
        $class = $p->getAttribute('class');
        $content = node_text($p);
        if (str_contains($class, 'p_title_description')) {
            $intro = $content;
        } elseif (str_contains($class, 'slashes_description') || $content === '' || preg_match('#^/+$#', $content)) {
            continue;   // la ligne de "/////" est ajoutée automatiquement par oeuvre.php
        } else {
            $paragraphs[] = $content;
        }
    }

    // Images de la galerie, dans l'ordre
    $images = [];
    foreach ($xp->query('//div[' . has_class('hero_work_thumb_image') . ']//img') as $img) {
        $images[] = $img->getAttribute('src');
    }
    $images = array_values(array_unique($images));

    // Vidéo : premier lien YouTube trouvé dans la page
    $videoUrl = null;
    foreach ($xp->query('//a[contains(@href, "youtu")] | //iframe[contains(@src, "youtu")]') as $node) {
        $videoUrl = $node->getAttribute('href') ?: $node->getAttribute('src');
        break;
    }

    // Année : la dernière ligne d'infos qui ressemble à une année
    $year = null;
    foreach ($info as $line) {
        if (preg_match('/\b(19|20)\d{2}\b/', $line, $m)) {
            $year = (int) $m[0];
        }
    }

    $works[$slug] = array_merge([
        'slug'          => $slug,
        'title'         => $listing['title'],   // titre de works.html (plus fiable que les <h2>)
        'subtitle'      => $span ? trim($span->textContent) : null,
        'year'          => $year,
        'info'          => implode("\n", $info),
        'intro'         => $intro,
        'description'   => implode("\n\n", $paragraphs),   // ligne vide = nouveau paragraphe
        'video_url'     => $videoUrl,
        'thumbnail'     => $listing['image'],
        'position'      => $position++,
        'in_public_art' => isset($publicArt[$slug]) ? 1 : 0,
        'in_films'      => isset($films[$slug]) ? 1 : 0,
        'on_home'       => isset($home[$slug]) ? 1 : 0,
        'home_hero'     => $slug === $homeHero ? 1 : 0,
        'images'        => $images,
    ], $overrides[$slug] ?? []);
}

// ---------------------------------------------------------------------
// 3. Les pages de texte : le contenu de <section class="cv_bio">
// ---------------------------------------------------------------------
$pages = [];
foreach (['news' => 'News', 'cv_bio' => 'CV / Bio', 'privacy_policy' => 'Privacy Policy'] as $slug => $title) {
    $xp = load_html("$root/$slug.html");
    $section = $xp->query('//section[' . has_class('cv_bio') . ']')->item(0);
    $html = '';
    foreach ($section->childNodes as $child) {
        $html .= $section->ownerDocument->saveHTML($child);
    }
    $pages[$slug] = ['title' => $title, 'content' => trim($html)];
}

// ---------------------------------------------------------------------
// 4. Mode essai : on affiche et on s'arrête
// ---------------------------------------------------------------------
if ($dryRun) {
    foreach ($works as $w) {
        $flags = implode(' ', array_keys(array_filter([
            'PUBLIC_ART' => $w['in_public_art'], 'FILMS' => $w['in_films'],
            'ACCUEIL' => $w['on_home'], 'A_LA_UNE' => $w['home_hero'],
        ])));
        printf("#%-2d %-30s %s %s\n", $w['position'], $w['slug'], $w['year'] ?? '----', $flags);
        printf("    titre : %s%s\n", $w['title'], $w['subtitle'] ? " {$w['subtitle']}" : '');
        printf("    infos : %s\n", str_replace("\n", ' | ', $w['info']));
        printf("    intro : %s\n", mb_strimwidth($w['intro'], 0, 90, '…'));
        printf("    texte : %d caractères, %d paragraphe(s)\n", mb_strlen($w['description']), $w['description'] === '' ? 0 : substr_count($w['description'], "\n\n") + 1);
        printf("    images: %d   vidéo : %s\n", count($w['images']), $w['video_url'] ?? '-');
    }
    foreach ($pages as $slug => $p) {
        printf("PAGE %-15s %d caractères de HTML\n", $slug, strlen($p['content']));
    }
    echo "\nMode essai : rien n'a été écrit. Relance sans --dry-run pour importer.\n";
    exit;
}

// ---------------------------------------------------------------------
// 5. Écriture en base - dans UNE transaction : tout ou rien
// ---------------------------------------------------------------------
require "$root/includes/db.php";

$exists      = db()->prepare('SELECT id FROM works WHERE slug = ?');
$insertWork  = db()->prepare(
    'INSERT INTO works (slug, title, subtitle, year, info, intro, description, video_url, thumbnail,
                        position, published, in_public_art, in_films, on_home, home_hero)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?)'
);
$insertImage = db()->prepare('INSERT INTO work_images (work_id, path, position) VALUES (?, ?, ?)');
$updateFlags = db()->prepare(
    'UPDATE works SET in_public_art = ?, in_films = ?, on_home = ?, home_hero = ?, position = ? WHERE id = ?'
);
$pageExists  = db()->prepare('SELECT COUNT(*) FROM pages WHERE slug = ?');
$insertPage  = db()->prepare('INSERT INTO pages (slug, title, content) VALUES (?, ?, ?)');

db()->beginTransaction();
try {
    foreach ($works as $w) {
        $flags = [$w['in_public_art'], $w['in_films'], $w['on_home'], $w['home_hero']];
        $exists->execute([$w['slug']]);

        if ($id = $exists->fetchColumn()) {
            // Déjà saisie dans l'admin : on garde ses textes et images,
            // on met seulement à jour les rubriques et l'ordre
            $updateFlags->execute([...$flags, $w['position'], $id]);
            echo "~  {$w['slug']} existait déjà : rubriques et position mises à jour\n";
            continue;
        }

        $insertWork->execute([
            $w['slug'], $w['title'], $w['subtitle'], $w['year'], $w['info'], $w['intro'],
            $w['description'], $w['video_url'], $w['thumbnail'], $w['position'], ...$flags,
        ]);
        $workId = db()->lastInsertId();
        foreach ($w['images'] as $i => $path) {
            $insertImage->execute([$workId, $path, $i]);
        }
        echo "+  {$w['slug']} (" . count($w['images']) . " images)\n";
    }

    foreach ($pages as $slug => $p) {
        $pageExists->execute([$slug]);
        if ($pageExists->fetchColumn()) {
            echo "~  page $slug existait déjà : ignorée\n";
            continue;
        }
        $insertPage->execute([$slug, $p['title'], $p['content']]);
        echo "+  page $slug\n";
    }

    db()->commit();
    echo "\nImport terminé.\n";
} catch (Throwable $e) {
    db()->rollBack();
    echo "\nERREUR, rien n'a été importé : " . $e->getMessage() . "\n";
    exit(1);
}
