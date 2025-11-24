const miniatures = document.querySelectorAll(".hero_work_thumb_image img");
const imagePrincipale = document.getElementById("MainImage");

miniatures.forEach(mini => {
  mini.addEventListener("click", () => {
  imagePrincipale.src = mini.src.replace("100", "500"); 
    });
    });


const thumbnail = document.querySelector('.thumbnail');
    const overlay = document.getElementById('overlay');
    const overlayImg = document.getElementById('overlayImg');
    const closeBtn = document.getElementById('closeBtn');

    // Quand on clique sur la miniature → afficher l'image
    thumbnail.addEventListener('click', () => {
      overlay.style.display = 'flex';
      overlayImg.src = thumbnail.src; // on reprend la même image
    });

    // Fermer en cliquant sur X
    closeBtn.addEventListener('click', () => {
      overlay.style.display = 'none';
    });

    // Fermer en cliquant en dehors de l'image
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        overlay.style.display = 'none';
      }
    });