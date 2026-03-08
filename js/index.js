const miniatures = document.querySelectorAll(".hero_work_thumb_image img");
const imagePrincipale = document.getElementById("MainImage");

miniatures.forEach(mini => {
  mini.addEventListener("click", () => {
  imagePrincipale.src = mini.src.replace("100", "500"); 
    });
    });


const liens = document.querySelectorAll('.nav-link');

liens.forEach(lien => {
    lien.addEventListener('click', function(e) {
        // Supprimer la classe 'active' de tous les liens
        document.querySelector('.nav-link.active').classList.remove('active');
        // Ajouter la classe 'active' au lien cliqué
        this.classList.add('active');
    });
});


document.getElementById("btnSuivant").onclick = function() {
    window.location.href = "page-suivante.html"; // Redirige vers la nouvelle URL
};


 // ─────────────────────────────────────────────
  //  CONFIGURATION — modifiez ces valeurs
  // ─────────────────────────────────────────────
  const DOSSIER_PARENT = '../img/FLANEUR/gallery_img/';       // dossier des originaux
  const DOSSIER_THUMB  = '../img/FLANEUR/gallery_img/thumbs/'; // dossier des miniatures

  // Liste de vos fichiers (nom uniquement, sans chemin)
  const photos = [
    'montagne.jpg',
    'mer.jpg',
    'foret.jpg',
    'ville.jpg',
    'portrait.jpg',
    'coucher-soleil.jpg',
    'lac.jpg',
    'desert.jpg',
    'cascade.jpg',
    'prairie.jpg',
    'route.jpg',
    'plage.jpg',
  ];
  // ─────────────────────────────────────────────

  // ── Construction de la galerie ──
  const gallery  = document.getElementById('gallery');
  const lightbox = document.getElementById('lightbox');
  const lbImg    = document.getElementById('lightbox-img');
  const lbCap    = document.getElementById('lightbox-caption');
  const lbCount  = document.getElementById('lb-counter');
  let current    = 0;

  function nomSansExtension(f) {
    return f.replace(/\.[^.]+$/, '').replace(/[-_]/g, ' ');
  }

photos.forEach((fichier, index) => {
    const card = document.createElement('div');
    card.className = 'thumb-card';
    card.dataset.index = index;

    const img = document.createElement('img');
    img.src = DOSSIER_THUMB + fichier;
    img.alt = nomSansExtension(fichier);
    img.loading = 'lazy';

    // Fallback visuel si la miniature n'existe pas (démo)
    img.onerror = () => {
      img.style.display = 'none';
      card.style.background = `hsl(${index * 29 % 360}, 18%, 22%)`;
      const ph = document.createElement('div');
      ph.style.cssText = 'position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:1.6rem;opacity:.4;';
      ph.textContent = '🖼';
      card.appendChild(ph);
    };

    const label = document.createElement('div');
    label.className = 'label';
    label.textContent = nomSansExtension(fichier);

    card.appendChild(img);
    card.appendChild(label);
    card.addEventListener('click', () => openLightbox(index));
    gallery.appendChild(card);
  });

  // ── Lightbox ──
  function openLightbox(index) {
    current = index;
    afficher();
    lightbox.classList.add('open');
  }

  function afficher() {
    const fichier = photos[current];
    lbImg.src = DOSSIER_PARENT + fichier;
    lbImg.alt = nomSansExtension(fichier);
    lbCap.textContent = nomSansExtension(fichier);
    lbCount.textContent = `${current + 1} / ${photos.length}`;
  }

  function fermer() { lightbox.classList.remove('open'); }

  function precedent() {
    current = (current - 1 + photos.length) % photos.length;
    afficher();
  }

  function suivant() {
    current = (current + 1) % photos.length;
    afficher();
  }

  document.getElementById('lb-close').addEventListener('click', fermer);
  document.getElementById('lb-prev').addEventListener('click', precedent);
  document.getElementById('lb-next').addEventListener('click', suivant);

  // Fermer en cliquant à l'extérieur de l'image
  lightbox.addEventListener('click', (e) => {
    if (e.target === lightbox) fermer();
  });

  // Navigation clavier
  document.addEventListener('keydown', (e) => {
    if (!lightbox.classList.contains('open')) return;
    if (e.key === 'ArrowLeft')  precedent();
    if (e.key === 'ArrowRight') suivant();
    if (e.key === 'Escape')     fermer();
  });


    // const thumbnail = document.getElementById("MainImage");
    // const overlay = document.getElementById('overlay');
    // const overlayImg = document.getElementById('overlayImg');
    // const closeBtn = document.getElementById('closeBtn');

    // // Quand on clique sur la miniature → afficher l'image
    // thumbnail.addEventListener('click', () => {
    //   overlay.style.display = 'flex';
    //   overlayImg.src = thumbnail.src; // on reprend la même image
    // });

    // // Fermer en cliquant sur X
    // closeBtn.addEventListener('click', () => {
    //   overlay.style.display = 'none';
    // });

    // // Fermer en cliquant en dehors de l'image
    // overlay.addEventListener('click', (e) => {
    //   if (e.target === overlay) {
    //     overlay.style.display = 'none';
    //   }
    // });