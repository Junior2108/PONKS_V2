<?php
// =====================================================================
// Modifier une page de texte : page_edit.php?slug=news
// =====================================================================

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';

require_login();

$slug = $_GET['slug'] ?? '';

$stmt = db()->prepare('SELECT slug, title, content FROM pages WHERE slug = ?');
$stmt->execute([$slug]);
$page = $stmt->fetch();

if (!$page) {
    http_response_code(404);
    exit('Page introuvable.');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $page['title']   = trim($_POST['title'] ?? '');
    $page['content'] = trim($_POST['content'] ?? '');

    if ($page['title'] === '' || mb_strlen($page['title']) > 200) {
        $error = 'Le titre est obligatoire (200 caractères maximum).';
    } else {
        db()->prepare('UPDATE pages SET title = ?, content = ? WHERE slug = ?')
            ->execute([$page['title'], $page['content'], $page['slug']]);

        flash('Page « ' . $page['title'] . ' » enregistrée.');
        header('Location: page_edit.php?slug=' . urlencode($page['slug']));
        exit;
    }
}

$pageTitle = 'Modifier ' . $page['title'];
require __DIR__ . '/../includes/admin_header.php';
?>
        <p><a href="pages.php">← Retour aux pages</a></p>

        <div class="admin-toolbar">
            <h1>Modifier « <?= e($page['title']) ?> »</h1>
            <a class="button" href="../<?= e(rawurlencode($page['slug'])) ?>.html" target="_blank">Voir sur le site</a>
        </div>

        <?php if ($error): ?>
            <p class="admin-error"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post" class="admin-form">
            <?= csrf_field() ?>

            <label for="title">Titre (onglet du navigateur)</label>
            <input type="text" id="title" name="title" value="<?= e($page['title']) ?>" required maxlength="200">

            <label for="content">Contenu (HTML)</label>
            <!-- e() ici : on affiche le code HTML DANS la zone de texte pour pouvoir le modifier,
                 sans que le navigateur l'interprète -->
            <textarea id="content" name="content" rows="30" class="code"><?= e($page['content']) ?></textarea>
            <p class="field-hint">
                Ce contenu est du HTML, affiché tel quel sur le site.
                Titre de section : &lt;p class="p_title"&gt;…&lt;/p&gt; —
                paragraphe : &lt;p&gt;…&lt;/p&gt; —
                lien : &lt;a href="https://…"&gt;…&lt;/a&gt;
            </p>

            <button type="submit">Enregistrer</button>
        </form>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
