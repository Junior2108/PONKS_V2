// =====================================================================
// Galerie de la page d'une œuvre : grande image + vignettes + lightbox.
//
// Props (envoyées par oeuvre.php) :
//   images : [{ src, alt }]  dans l'ordre d'affichage
//   title  : titre de l'œuvre (texte alternatif par défaut)
//
// Reprend les classes CSS existantes (hero_work_image, thumbnail...) :
// l'apparence de la page ne change pas.
// =====================================================================

import { useEffect, useState } from 'react'
import './WorkGallery.css'

export default function WorkGallery({ images = [], title = '' }) {
  // Index de l'image affichée en grand
  const [current, setCurrent] = useState(0)
  // Lightbox ouverte ou fermée
  const [open, setOpen] = useState(false)

  const count = images.length
  // Boucle : après la dernière image on revient à la première, et inversement
  const go = (step) => setCurrent((index) => (index + step + count) % count)

  // Clavier (Échap, flèches) et blocage du défilement, seulement quand la lightbox est ouverte
  useEffect(() => {
    if (!open) return

    const onKey = (e) => {
      if (e.key === 'Escape') setOpen(false)
      if (e.key === 'ArrowLeft') go(-1)
      if (e.key === 'ArrowRight') go(1)
    }
    document.addEventListener('keydown', onKey)
    document.body.style.overflow = 'hidden'

    // Fonction de "nettoyage" : appelée à la fermeture de la lightbox
    return () => {
      document.removeEventListener('keydown', onKey)
      document.body.style.overflow = ''
    }
  }, [open, count])

  if (count === 0) return null

  const image = images[current]

  return (
    <>
      <div className="hero_work_image">
        <img
          height="768"
          width="768"
          src={image.src}
          alt={image.alt || title}
          className="work_gallery_main"
          onClick={() => setOpen(true)}
        />
      </div>

      <div className="hero_work_thumb_image">
        {images.map((thumb, index) => (
          <div className="thumbnail_block" key={thumb.src}>
            <img
              height="200"
              width="200"
              src={thumb.src}
              alt={thumb.alt || title}
              className={'thumbnail' + (index === current ? ' is-current' : '')}
              loading="lazy"
              onClick={() => setCurrent(index)}
            />
          </div>
        ))}
      </div>

      {open && (
        // Un clic sur le fond sombre ferme la lightbox
        <div
          className="lightbox"
          role="dialog"
          aria-modal="true"
          aria-label={title}
          onClick={(e) => e.target === e.currentTarget && setOpen(false)}
        >
          {/* key : force React à recréer l'image, ce qui relance l'animation de fondu */}
          <img key={image.src} className="lightbox_img" src={image.src} alt={image.alt || title} />

          <button className="lightbox_close" aria-label="Close" onClick={() => setOpen(false)}>✕</button>

          {count > 1 && (
            <>
              <button className="lightbox_arrow lightbox_prev" aria-label="Previous" onClick={() => go(-1)}>❮</button>
              <button className="lightbox_arrow lightbox_next" aria-label="Next" onClick={() => go(1)}>❯</button>
            </>
          )}

          <div className="lightbox_counter">{current + 1} / {count}</div>
        </div>
      )}
    </>
  )
}
