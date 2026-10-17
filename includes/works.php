<?php
// =====================================================================
// Lecture des œuvres pour les pages publiques
// (nécessite includes/db.php)
// =====================================================================

// Les filtres possibles => la colonne correspondante en base.
// LISTE BLANCHE OBLIGATOIRE : un nom de colonne ne peut PAS être passé
// avec un "?" dans une requête préparée (seules les VALEURS peuvent l'être).
// On ne met donc dans le SQL que des noms qui viennent de ce tableau.
const WORK_FILTERS = [
    'public_art' => 'in_public_art',
    'films'      => 'in_films',
    'home'       => 'on_home',
];

// Les œuvres publiées, dans l'ordre, avec leur image de galerie.
//   $filter    : une clé de WORK_FILTERS, ou null pour toutes les œuvres
//   $excludeId : id d'une œuvre à ne pas inclure (celle qu'on est en train d'afficher)
function published_works(?string $filter = null, ?int $excludeId = null): array
{
    // COALESCE(a, b) : a s'il n'est pas NULL, sinon b.
    // Pas d'image de couverture ? On prend la première image de la galerie.
    $sql = 'SELECT id, slug, title,
                   COALESCE(thumbnail,
                            (SELECT path FROM work_images
                             WHERE work_images.work_id = works.id
                             ORDER BY position, id LIMIT 1)) AS cover
            FROM works
            WHERE published = 1';
    $params = [];

    if ($filter !== null) {
        $sql .= ' AND ' . WORK_FILTERS[$filter] . ' = 1';
    }
    if ($excludeId !== null) {
        $sql .= ' AND id <> ?';
        $params[] = $excludeId;
    }

    $stmt = db()->prepare($sql . ' ORDER BY position, id');
    $stmt->execute($params);

    return $stmt->fetchAll();
}

// Une seule œuvre publiée répondant à une condition (ou null)
//   $where : condition SQL ÉCRITE DANS LE CODE, jamais venue de l'utilisateur
function first_published_work(string $where): ?array
{
    $work = db()->query("SELECT * FROM works WHERE published = 1 AND $where ORDER BY position, id LIMIT 1")->fetch();

    return $work ?: null;
}
