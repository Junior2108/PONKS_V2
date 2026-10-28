// Sélectionner tous les liens de navigation*/
const liens = document.querySelectorAll('.nav-link');

liens.forEach(lien => {
    lien.addEventListener('click', function(e) {
        // Supprimer la classe 'active' de tous les liens
        document.querySelector('.nav-link.active')?.classList.remove('active');
        // Ajouter la classe 'active' au lien cliqué
        this.classList.add('active');
    });
});

// La galerie et la lightbox de la page œuvre sont maintenant en React :
// voir front/src/islands/WorkGallery.jsx
