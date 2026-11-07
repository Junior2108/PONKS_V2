# PONKS_V2

Site portfolio de PONKS : pages publiques et interface d'administration en PHP / MariaDB,
avec quelques composants interactifs en React.

## Structure du projet
- `index.php`, `works.php`, `oeuvre.php`, `films.php`, `page.php` — pages publiques
  (les anciennes adresses en `.html` sont conservées grâce au `.htaccess`)
- `admin/` — interface d'administration (œuvres, images, pages)
- `includes/` — configuration, connexion à la base, gabarits communs (inaccessible depuis le navigateur)
- `database/` — schéma SQL et scripts d'import
- `css/`, `img/` — styles et images
- `js/index.js` — petit script commun
- `front/` — sources des composants React (inaccessible depuis le navigateur)
- `js/react/` — composants React compilés, chargés par les pages PHP

## Installation locale
1. Copier `includes/config.example.php` en `includes/config.php` et y mettre les accès à la base.
2. Créer la base avec `database/schema.sql`, puis appliquer les migrations.

## Composants React
PHP génère toute la page. Les zones interactives sont marquées dans le HTML :

```html
<div data-react="WorkGallery" data-props='{"images": [...]}'> ... </div>
```

`front/src/main.jsx` remplace ces zones par le composant React du même nom.

```bash
cd front
npm install     # une seule fois
npm run dev     # recompile à chaque modification
npm run build   # avant la mise en ligne
```

## Mise en ligne (FTP)
Envoyer tout le site **sauf** `front/`, `includes/config.php` (déjà présent en ligne) et `ftp.txt`.
Ne pas oublier `js/react/` après un `npm run build`.
