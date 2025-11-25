# PONKS_V2

Site statique simple pour le portfolio PONKS.

## Prérequis
- Node.js (>= 16)
- npm

## Scripts utiles
- `npm start` — Lance un serveur de développement (live-server) sur le port 3000 et ouvre `index.html`.
- `npm run build` — Copie les fichiers dans `dist` (dossier prêt pour déploiement).
- `npm run deploy:gh-pages` — Déploie le contenu du dossier `dist` sur la branche `gh-pages` (utilise la librairie `gh-pages`).

## Déploiement
1. Installer les dépendances :

```bash
npm install
```

2. Lancer localement :

```bash
npm start
```

3. Générer la build :

```bash
npm run build
```

4. Déployer sur GitHub Pages :

```bash
npm run deploy:gh-pages
```

Alternatives: vous pouvez aussi déployer le dossier `dist` sur Netlify, Vercel ou un autre hébergeur de fichiers statiques.

## Structure du projet
- `index.html`, `works.html`, `public_art.html`, `cv_bio.html`, `template_carousel.html` — pages HTML
- `css/` — styles
- `js/` — scripts
- `img/` — images

## Notes
- Le site est statique; aucun backend n'est inclus.
- Si vous avez besoin d'intégration CI/CD, je peux ajouter un fichier GitHub Actions de build et déploiement automatisé sur demande.
