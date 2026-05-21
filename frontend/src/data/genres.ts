export type CategoryId = 'noire' | 'blanche' | 'sentimentale' | 'imaginaire'

export interface GenreItem {
  id: string
  name: string
  bridges?: CategoryId[]
}

export interface GenreCategory {
  id: CategoryId
  name: string
  color: string
  lightColor: string
  textColor: string
  adjacentCategories: CategoryId[]
  genres: GenreItem[]
}

export const GENRE_CATEGORIES: GenreCategory[] = [
  {
    id: 'noire',
    name: 'Littérature noire',
    color: '#7c3141',
    lightColor: '#fdf1f3',
    textColor: '#581829',
    adjacentCategories: ['blanche'],
    genres: [
      { id: 'polar', name: 'Polar' },
      { id: 'thriller', name: 'Thriller' },
      { id: 'roman-noir', name: 'Roman noir' },
      { id: 'true-crime', name: 'True crime' },
      { id: 'cosy-mystery', name: 'Cosy mystery' },
      { id: 'polar-terroir', name: 'Polar de terroir' },
      { id: 'thriller-horrifique', name: 'Thriller horrifique' },
      { id: 'roman-espionnage', name: "Roman d'espionnage" },
      { id: 'thriller-psychologique', name: 'Thriller psychologique' },
      { id: 'thriller-politique', name: 'Thriller politique' },
    ],
  },
  {
    id: 'blanche',
    name: 'Littérature blanche',
    color: '#94a3b8',
    lightColor: '#f8fafc',
    textColor: '#475569',
    adjacentCategories: ['noire', 'sentimentale', 'imaginaire'],
    genres: [
      { id: 'roman-apprentissage', name: "Roman d'apprentissage" },
      { id: 'roman-aventures', name: "Roman d'aventures" },
      { id: 'autofiction', name: 'Autofiction' },
      { id: 'realisme-magique', name: 'Réalisme magique', bridges: ['imaginaire'] },
      { id: 'roman-historique', name: 'Roman historique', bridges: ['sentimentale'] },
      { id: 'thriller-archeologique', name: 'Thriller archéologique', bridges: ['noire'] },
      { id: 'roman-secrets-famille', name: 'Roman à secrets de famille' },
    ],
  },
  {
    id: 'sentimentale',
    name: 'Littérature sentimentale',
    color: '#f472b6',
    lightColor: '#fdf2f8',
    textColor: '#be185d',
    adjacentCategories: ['imaginaire', 'blanche'],
    genres: [
      { id: 'romance-erotique', name: 'Romance érotique' },
      { id: 'dark-romance', name: 'Dark romance', bridges: ['noire'] },
      { id: 'new-romance', name: 'New romance / New adult' },
      { id: 'romance-lgbt', name: 'Romance LGBT' },
      { id: 'comedie-romantique', name: 'Comédie romantique' },
      { id: 'chick-lit', name: 'Chick-lit' },
      { id: 'romantasy', name: 'Romantasy', bridges: ['imaginaire'] },
      { id: 'feel-good', name: 'Feel good' },
      { id: 'romance-policiere', name: 'Romance policière', bridges: ['noire'] },
      { id: 'romance-historique', name: 'Romance historique', bridges: ['blanche'] },
    ],
  },
  {
    id: 'imaginaire',
    name: "Littérature de l'imaginaire",
    color: '#fb923c',
    lightColor: '#fff7ed',
    textColor: '#c2410c',
    adjacentCategories: ['sentimentale', 'blanche'],
    genres: [
      { id: 'fantasy', name: 'Fantasy' },
      { id: 'heroic-fantasy', name: 'Heroic Fantasy' },
      { id: 'urban-fantasy', name: 'Urban Fantasy' },
      { id: 'dark-fantasy', name: 'Dark Fantasy' },
      { id: 'fantasy-epique', name: 'Fantasy épique' },
      { id: 'fantasy-historique', name: 'Fantasy historique', bridges: ['blanche'] },
      { id: 'cosy-fantasy', name: 'Cosy Fantasy' },
      { id: 'grimdark', name: 'Grimdark' },
      { id: 'fantastique', name: 'Fantastique' },
      { id: 'gothique', name: 'Gothique' },
      { id: 'horreur', name: 'Horreur' },
      { id: 'science-fiction', name: 'Science-fiction' },
      { id: 'hard-sf', name: 'Hard SF' },
      { id: 'space-opera', name: 'Space opera' },
      { id: 'cyberpunk', name: 'Cyberpunk' },
      { id: 'steampunk', name: 'Steampunk' },
      { id: 'solarpunk', name: 'Solarpunk / Hopepunk' },
      { id: 'dystopie', name: 'Dystopie' },
      { id: 'utopie', name: 'Utopie' },
      { id: 'uchronie', name: 'Uchronie' },
      { id: 'post-apocalyptique', name: 'Post-apocalyptique' },
      { id: 'climate-fiction', name: 'Climate Fiction' },
      { id: 'litterature-ecologique', name: 'Littérature écologique' },
      { id: 'nature-writing', name: 'Nature writing' },
    ],
  },
]

const CATEGORY_PROXIMITY: Record<CategoryId, Record<CategoryId, number>> = {
  noire:        { noire: 1.0, blanche: 0.65, sentimentale: 0.35, imaginaire: 0.25 },
  blanche:      { noire: 0.65, blanche: 1.0, sentimentale: 0.55, imaginaire: 0.50 },
  sentimentale: { noire: 0.35, blanche: 0.55, sentimentale: 1.0, imaginaire: 0.75 },
  imaginaire:   { noire: 0.25, blanche: 0.50, sentimentale: 0.75, imaginaire: 1.0 },
}

export function getGenreName(id: string): string {
  for (const cat of GENRE_CATEGORIES) {
    const genre = cat.genres.find((g) => g.id === id)
    if (genre) return genre.name
  }
  return id
}

export function getCategoryForGenre(id: string): GenreCategory | undefined {
  return GENRE_CATEGORIES.find((cat) => cat.genres.some((g) => g.id === id))
}

export interface FusionInfo {
  score: number
  label: string
  description: string
  color: string
}

export function computeFusion(selectedIds: string[]): FusionInfo {
  if (selectedIds.length === 0) return { score: 0, label: '', description: '', color: '' }
  if (selectedIds.length === 1) {
    return { score: 1, label: 'Genre unique', description: 'Un genre défini, une direction claire.', color: '#22c55e' }
  }

  const genreData = selectedIds.map((id) => {
    const cat = getCategoryForGenre(id)
    const genre = cat?.genres.find((g) => g.id === id)
    return { id, catId: cat?.id as CategoryId | undefined, bridges: genre?.bridges ?? [] }
  }).filter((g) => g.catId)

  const categories = new Set<CategoryId>(genreData.map((g) => g.catId!))
  const catArray = [...categories] as CategoryId[]

  if (catArray.length === 1) {
    return {
      score: 0.95,
      label: 'Harmonie naturelle',
      description: 'Genres du même univers. Le mélange coule de source.',
      color: '#22c55e',
    }
  }

  // Paires de catégories reliées par un genre-pont sélectionné
  const bridgedPairs = new Set<string>()
  for (const gd of genreData) {
    for (const bridge of gd.bridges) {
      if (categories.has(bridge as CategoryId) && bridge !== gd.catId) {
        bridgedPairs.add([gd.catId, bridge].sort().join('|'))
      }
    }
  }

  let totalProximity = 0
  let pairs = 0
  for (let i = 0; i < catArray.length; i++) {
    for (let j = i + 1; j < catArray.length; j++) {
      const ci = catArray[i] as CategoryId
      const cj = catArray[j] as CategoryId
      const key = [ci, cj].sort().join('|')
      const base = CATEGORY_PROXIMITY[ci][cj]
      // Un genre-pont actif élève la proximité de la paire à 0.8 minimum
      totalProximity += bridgedPairs.has(key) ? Math.max(base, 0.8) : base
      pairs++
    }
  }

  const score = Math.min(1, totalProximity / pairs)

  if (score >= 0.7) {
    return { score, label: 'Fusion créative', description: 'Ces genres se complètent bien. Un mélange accessible.', color: '#84cc16' }
  } else if (score >= 0.45) {
    return { score, label: 'Alliance audacieuse', description: 'Un croisement original. Ambitieux mais faisable.', color: '#f59e0b' }
  } else {
    return { score, label: 'Territoire inexploré', description: 'Ces genres rarement mélangés — un vrai défi de maîtrise.', color: '#ef4444' }
  }
}
