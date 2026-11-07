<?php
// =====================================================================
// Liste des pages de texte (news, cv/bio, privacy policy)
// =====================================================================

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';

require_login();

$pages = db()->query('SELECT slug, title, updated_at FROM pages ORDER BY title')->fetchAll();

$pageTitle = 'Pages';
require __DIR__ . '/../includes/admin_header.php';
?>
        <h1>Pages</h1>

        <?php if (!$pages): ?>
            <p class="admin-empty">Aucune page. Lance l'import : php database/import_html.php</p>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>Dernière modification</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $page): ?>
                            <tr>
                                <td><strong><?= e($page['title']) ?></strong></td>
                                <!-- date() reformate la date SQL "2026-10-17 21:30:00" en "17/10/2026 21:30" -->
                                <td><?= e(date('d/m/Y H:i', strtotime($page['updated_at']))) ?></td>
                                <td class="admin-actions">
                                    <a href="page_edit.php?slug=<?= e(urlencode($page['slug'])) ?>">Modifier</a>
                                    <a href="../<?= e(rawurlencode($page['slug'])) ?>.html" target="_blank">Voir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
