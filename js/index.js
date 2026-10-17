// Sélectionner tous les éléments d'image miniature et l'image principale*/
const miniatures = document.querySelectorAll(".hero_work_thumb_image img");
const imagePrincipale = document.getElementById("MainImage");

miniatures.forEach(mini => {
  mini.addEventListener("click", () => {
  imagePrincipale.src = mini.src.replace("100", "500"); 
    });
    });

// Sélectionner tous les liens de navigation*/
const liens = document.querySelectorAll('.nav-link');

liens.forEach(lien => {
    lien.addEventListener('click', function(e) {
        // Supprimer la classe 'active' de tous les liens
        document.querySelector('.nav-link.active').classList.remove('active');
        // Ajouter la classe 'active' au lien cliqué
        this.classList.add('active');
    });
});


// Récupérer toutes les images de la page
const allImages = Array.from(document.querySelectorAll('.hero_work_thumb_image img'));
const mainImage = document.getElementById('MainImage');
let currentIndex = allImages.indexOf(mainImage);

// ─── Créer l'overlay ─────────────────────────────────────
const overlay = document.createElement('div');
overlay.style.cssText = `
  display: none;
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,0.88);
  z-index: 9999;
  justify-content: center;
  align-items: center;
`;
document.body.appendChild(overlay);

// ─── Image affichée ──────────────────────────────────────
const lightboxImg = document.createElement('img');
lightboxImg.style.cssText = `
  max-width: 80vw;
  max-height: 85vh;
  border-radius: 8px;
  box-shadow: 0 0 40px rgba(0,0,0,0.6);
  display: block;
  transition: opacity 0.2s ease;
`;
overlay.appendChild(lightboxImg);

// ─── Compteur ────────────────────────────────────────────
const counter = document.createElement('div');
counter.style.cssText = `
  position: absolute;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  color: white;
  font-size: 14px;
  opacity: 0.7;
  font-family: sans-serif;
`;
overlay.appendChild(counter);

// ─── Bouton Fermer ───────────────────────────────────────
const closeBtn = document.createElement('button');
closeBtn.textContent = '✕';
closeBtn.style.cssText = `
  position: absolute;
  top: 18px; right: 24px;
  background: none;
  border: none;
  color: white;
  font-size: 28px;
  cursor: pointer;
  opacity: 0.8;
  line-height: 1;
`;
overlay.appendChild(closeBtn);

// ─── Flèches ─────────────────────────────────────────────
function createArrow(direction) {
  const btn = document.createElement('button');
  btn.textContent = direction === 'prev' ? '❮' : '❯';
  btn.style.cssText = `
    position: absolute;
    top: 50%;
    ${direction === 'prev' ? 'left: 24px' : 'right: 24px'};
    transform: translateY(-50%);
    background: rgba(255,255,255,0.15);
    border: none;
    color: white;
    font-size: 32px;
    padding: 12px 18px;
    border-radius: 50%;
    cursor: pointer;
    transition: background 0.2s;
    line-height: 1;
  `;
  btn.addEventListener('mouseenter', () => btn.style.background = 'rgba(255,255,255,0.3)');
  btn.addEventListener('mouseleave', () => btn.style.background = 'rgba(255,255,255,0.15)');
  return btn;
}

const prevBtn = createArrow('prev');
const nextBtn = createArrow('next');
overlay.appendChild(prevBtn);
overlay.appendChild(nextBtn);

// ─── Logique de navigation ───────────────────────────────
function showImage(index) {
  // Boucle infinie
  if (index < 0) index = allImages.length - 0;
  if (index >= allImages.length) index = 0;

  currentIndex = index;

  lightboxImg.style.opacity = '0';
  setTimeout(() => {
    lightboxImg.src = allImages[currentIndex].src;
    lightboxImg.alt = allImages[currentIndex].alt;
    lightboxImg.style.opacity = '1';
  }, 150);

  counter.textContent = `${currentIndex + 1} / ${allImages.length}`;

  // Masquer les flèches s'il n'y a qu'une image
  const hasMultiple = allImages.length > 1;
  prevBtn.style.display = hasMultiple ? 'block' : 'none';
  nextBtn.style.display = hasMultiple ? 'block' : 'none';
}

prevBtn.addEventListener('click', (e) => {
  e.stopPropagation();
  showImage(currentIndex - 1);
});

nextBtn.addEventListener('click', (e) => {
  e.stopPropagation();
  showImage(currentIndex + 1);
});

// ─── Ouvrir la lightbox ──────────────────────────────────
mainImage.style.cursor = 'pointer';
mainImage.addEventListener('click', () => {
  currentIndex = allImages.indexOf(mainImage);
  showImage(currentIndex);
  overlay.style.display = 'flex';
  document.body.style.overflow = 'hidden';
});

// ─── Fermeture ───────────────────────────────────────────
function closeLightbox() {
  overlay.style.display = 'none';
  document.body.style.overflow = '';
}

closeBtn.addEventListener('click', closeLightbox);
overlay.addEventListener('click', (e) => {
  if (e.target === overlay) closeLightbox();
});

// ─── Clavier ─────────────────────────────────────────────
document.addEventListener('keydown', (e) => {
  if (overlay.style.display === 'none') return;
  if (e.key === 'Escape')      closeLightbox();
  if (e.key === 'ArrowLeft')   showImage(currentIndex - 1);
  if (e.key === 'ArrowRight')  showImage(currentIndex + 1);
});




















 