<?php
// =====================================================================
// Pages de texte : news, cv/bio, privacy policy...
// Adresse publique : news.html  (le .htaccess appelle en interne page.php?slug=news)
// =====================================================================

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

$slug = $_GET['slug'] ?? '';

$stmt = db()->prepare('SELECT title, content FROM pages WHERE slug = ?');
$stmt->execute([$slug]);
$page = $stmt->fetch();

if (!$page) {
    http_response_code(404);
}

$pageTitle  = 'PONKS - ' . ($page['title'] ?? 'Not found');
$activePage = $slug;   // surligne "news" ou "cv/bio" dans le menu (si c'en est un)
require __DIR__ . '/includes/header.php';
?>
            <section class="cv_bio">
                <?php if ($page): ?>
                    <?php
                    // ATTENTION : ici le contenu est affiché SANS e(), car c'est du HTML
                    // mis en forme (titres, listes, liens...). On peut se le permettre
                    // uniquement parce que ce contenu n'est modifiable QUE par
                    // l'administrateur connecté. Jamais avec un texte venant d'un visiteur !
                    echo $page['content'];
                    ?>
                <?php else: ?>
                    <p class="p_title">This page does not exist.</p>
                <?php endif; ?>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
