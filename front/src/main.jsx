// =====================================================================
// Point d'entrée : "îlots" React dans des pages générées par PHP.
//
// PHP écrit le HTML complet (pour Google et les visiteurs sans JS),
// et marque les zones à rendre interactives :
//
//   <div data-react="WorkGallery" data-props='{"images": [...]}'> ... </div>
//
// Au chargement, React remplace le contenu de chaque zone par le
// composant du même nom, avec les données de data-props.
// =====================================================================

import { createRoot } from 'react-dom/client'
import WorkGallery from './islands/WorkGallery.jsx'

// Composants utilisables depuis PHP : 'nom dans data-react' => composant
const islands = {
  WorkGallery,
}

document.querySelectorAll('[data-react]').forEach((element) => {
  const Component = islands[element.dataset.react]
  if (!Component) {
    console.warn(`Composant React inconnu : ${element.dataset.react}`)
    return
  }

  let props = {}
  try {
    props = JSON.parse(element.dataset.props || '{}')
  } catch {
    // Données illisibles : on garde le HTML de PHP tel quel
    console.warn(`data-props invalide pour ${element.dataset.react}`)
    return
  }

  createRoot(element).render(<Component {...props} />)
})
