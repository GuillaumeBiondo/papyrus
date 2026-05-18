import type { EditionSettings, EditionDocumentEntry } from '@/types'
import type { Arc } from '@/types'

// ── Constantes physiques ──────────────────────────────────────
const MM_TO_PX = 3.7795
const PT_TO_PX = 96 / 72

export function mmToPx(mm: number) { return mm * MM_TO_PX }
export function cmToPx(cm: number) { return cm * 10 * MM_TO_PX }

// ── Chiffres romains ──────────────────────────────────────────
const ROMAN = ['i','ii','iii','iv','v','vi','vii','viii','ix','x','xi','xii','xiii','xiv','xv','xvi','xvii','xviii','xix','xx']
export function toRoman(n: number): string { return ROMAN[n - 1] ?? String(n) }

// ── Chiffres en lettres (français) ────────────────────────────
const FR_WORDS = [
  'un','deux','trois','quatre','cinq','six','sept','huit','neuf','dix',
  'onze','douze','treize','quatorze','quinze','seize','dix-sept','dix-huit','dix-neuf','vingt',
  'vingt et un','vingt-deux','vingt-trois','vingt-quatre','vingt-cinq',
  'vingt-six','vingt-sept','vingt-huit','vingt-neuf','trente',
]
function toFrenchText(n: number): string { return FR_WORDS[n - 1] ?? String(n) }

// ── Application de la numérotation à un titre de chapitre ─────
function applyNumbering(title: string, n: number, style: string): string {
  let num = ''
  if (style === 'roman')  num = toRoman(n).toUpperCase()
  if (style === 'arabic') num = String(n)
  if (style === 'text')   num = toFrenchText(n)
  if (!num) return title
  return title ? `${num} — ${title}` : num
}

// ── Strip HTML ────────────────────────────────────────────────
export function stripHtml(html: string): string {
  return html.replace(/<[^>]*>/g, ' ').replace(/&[a-z]+;/gi, ' ').replace(/\s+/g, ' ').trim()
}

// ── Extraction des blocs de premier niveau ───────────────────
export function extractBlocks(html: string): string[] {
  if (!html) return []
  const div = document.createElement('div')
  div.innerHTML = html
  const blocks: string[] = []
  div.childNodes.forEach(node => {
    if (node.nodeType === Node.ELEMENT_NODE) {
      blocks.push((node as Element).outerHTML)
    } else if (node.nodeType === Node.TEXT_NODE && node.textContent?.trim()) {
      blocks.push(`<p>${node.textContent}</p>`)
    }
  })
  return blocks
}

// ── Métriques de pagination ───────────────────────────────────
export interface PaginationMetrics {
  pageWidthPx: number
  pageHeightPx: number
  marginTopPx: number
  marginBottomPx: number
  marginInnerPx: number
  marginOuterPx: number
  contentWidthPx: number
  contentHeightPx: number
  snappedContentHeightPx: number  // multiple exact de lineHeightPx → pas de ligne coupée
  fontSizePx: number
  lineHeightPx: number
  charsPerLine: number
  linesPerPage: number
  headerHeightPx: number
  footerHeightPx: number
  textAlign: 'justify' | 'left'
  chapterTitleSpaceBeforeEm: number
  chapterTitleSpaceAfterEm: number
  headerRule: boolean
  footerRule: boolean
  ruleSpaceBeforePx: number
  ruleSpaceAfterPx: number
}

export function computeMetrics(settings: EditionSettings): PaginationMetrics {
  const pageWidthPx  = cmToPx(settings.page.width)
  const pageHeightPx = cmToPx(settings.page.height)

  const marginTopPx    = mmToPx(settings.page.margin_top)
  const marginBottomPx = mmToPx(settings.page.margin_bottom)
  const marginInnerPx  = mmToPx(settings.page.margin_inner)
  const marginOuterPx  = mmToPx(settings.page.margin_outer)

  const headerHeightPx = 22
  const footerHeightPx = 22

  const contentWidthPx  = pageWidthPx - marginInnerPx - marginOuterPx
  const contentHeightPx = pageHeightPx - marginTopPx - marginBottomPx - headerHeightPx - footerHeightPx

  const fontSizePx   = settings.text.body_font_size * PT_TO_PX
  const lineHeightPx = fontSizePx * settings.text.line_height

  const avgCharWidthPx = fontSizePx * 0.5
  const charsPerLine   = Math.max(10, Math.floor(contentWidthPx / avgCharWidthPx))
  const linesPerPage   = Math.max(3, Math.floor(contentHeightPx / lineHeightPx))

  // Hauteur snappée = nombre entier de lignes → aucune ligne coupée à mi-hauteur
  const snappedContentHeightPx = linesPerPage * lineHeightPx

  return {
    pageWidthPx, pageHeightPx,
    marginTopPx, marginBottomPx, marginInnerPx, marginOuterPx,
    contentWidthPx, contentHeightPx, snappedContentHeightPx,
    fontSizePx, lineHeightPx,
    charsPerLine, linesPerPage,
    headerHeightPx, footerHeightPx,
    textAlign: settings.text.alignment === 'left' ? 'left' : 'justify',
    chapterTitleSpaceBeforeEm: settings.titles.space_before ?? 3.0,
    chapterTitleSpaceAfterEm:  settings.titles.space_after  ?? 2.0,
    headerRule: settings.headers.header_rule ?? true,
    footerRule: settings.headers.footer_rule ?? true,
    ruleSpaceBeforePx: (settings.headers.rule_space_before ?? 2) * PT_TO_PX,
    ruleSpaceAfterPx:  (settings.headers.rule_space_after  ?? 4) * PT_TO_PX,
  }
}

// ── Estimation de lignes pour un bloc HTML ─────────────────────
// extraLines : lignes supplémentaires à ajouter (ex: marges d'un séparateur)
function blockLines(html: string, charsPerLine: number, extraLines = 0): number {
  if (html.includes('scene-sep') || html.startsWith('<hr')) {
    // 1 ligne pour le symbole + marges exprimées en lignes (1 em ≈ 1 ligne)
    return 1 + extraLines
  }
  const text = stripHtml(html)
  if (!text) return 0
  return Math.ceil(text.length / charsPerLine) + 1 // +1 = saut de paragraphe
}

// ── Types de pages ─────────────────────────────────────────────
export type PageKind =
  | 'blank'
  | 'auto_title'
  | 'auto_copyright'
  | 'auto_toc'
  | 'liminary'
  | 'part'
  | 'chapter'
  | 'body'
  | 'annex'

export interface BookPage {
  id: string
  kind: PageKind
  physicalNum: number    // numéro physique (1, 2, 3…)
  displayNum: string     // 'i','ii'… ou '1','2'…
  isLiminary: boolean
  isVerso: boolean       // true = page gauche (paire)
  showHeader: boolean
  headerLeft: string
  headerRight: string
  showFooter: boolean
  // Contenu structurel
  bigTitle?: string
  subTitle?: string
  docLabel?: string
  // Contenu texte — liste de blocs HTML
  blocks: string[]
  // Métadonnées de contexte (pour résolution des en-têtes)
  chapterTitle: string
  arcTitle: string
  bookTitle: string
  authorName: string
}

// ── Résolution d'un champ d'en-tête ───────────────────────────
function resolveField(
  field: string,
  ctx: { bookTitle: string; chapterTitle: string; arcTitle: string; authorName: string; displayNum: string },
): string {
  switch (field) {
    case 'book_title':    return ctx.bookTitle
    case 'chapter_title': return ctx.chapterTitle
    case 'arc_title':     return ctx.arcTitle
    case 'author_name':   return ctx.authorName
    case 'page_number':   return ctx.displayNum
    case 'none':          return ''
    default:              return ''
  }
}

// ── Pagination principale ──────────────────────────────────────
export interface PaginatorInput {
  settings: EditionSettings
  arcs: Arc[]
  documents: EditionDocumentEntry[]
  bookTitle: string
  authorName: string
  // Contenu des documents (chargé séparément : id → html)
  documentContents: Record<number, string>
}

export function paginate(input: PaginatorInput): BookPage[] {
  const { settings, arcs, documents, bookTitle, authorName, documentContents } = input
  const m = computeMetrics(settings)

  const pages: BookPage[] = []
  let physNum = 0
  let textNum = 0  // compteur unifié — démarre à la 1ère page de texte (après titre+copyright)

  const liminaries = documents.filter(d => d.category === 'liminary' && d.is_enabled)
  const annexes    = documents.filter(d => d.category === 'annex'    && d.is_enabled)

  function nextPhys() { return ++physNum }
  function nextText() { return ++textNum }  // à appeler pour toute page après copyright

  function isVerso(n: number) { return n % 2 === 0 }

  function makeCtx(chapterTitle = '', arcTitle = '', displayNum: string): {
    bookTitle: string; chapterTitle: string; arcTitle: string; authorName: string; displayNum: string
  } {
    return { bookTitle, chapterTitle, arcTitle, authorName, displayNum }
  }

  // Pousse des pages de corps avec du contenu HTML découpé
  function pushBodyPages(
    htmlBlocks: string[],
    chapterTitle: string,
    arcTitle: string,
    isFirstOfChapter = false,
  ) {
    let linesUsed = 0
    let currentBlocks: string[] = []
    let firstPage = isFirstOfChapter

    // Réserve des lignes pour le titre de chapitre sur la première page
    if (isFirstOfChapter) {
      const spacingLines = Math.round(
        (settings.titles.space_before ?? 3.0) + (settings.titles.space_after ?? 2.0),
      )
      const titleLines = Math.ceil(chapterTitle.length / m.charsPerLine) + spacingLines
      linesUsed = titleLines
    }

    function flush() {
      const n = nextPhys()
      const textN = nextText()
      const display = String(textN)
      const ctx = makeCtx(chapterTitle, arcTitle, display)
      pages.push({
        id: `body-${n}`,
        kind: 'body',
        physicalNum: n,
        displayNum: display,
        isLiminary: false,
        isVerso: isVerso(n),
        showHeader: !firstPage,
        headerLeft:  resolveField(settings.headers.left_field,  ctx),
        headerRight: resolveField(settings.headers.right_field, ctx),
        showFooter: true,
        bigTitle: firstPage ? chapterTitle : undefined,
        blocks: [...currentBlocks],
        chapterTitle, arcTitle, bookTitle, authorName,
      })
      firstPage = false
      currentBlocks = []
      linesUsed = 0
    }

    const sepExtraLines = Math.round(
      (settings.structure.separator_space_before ?? 1.5) +
      (settings.structure.separator_space_after  ?? 1.0),
    )

    for (const block of htmlBlocks) {
      const isSep = block.includes('scene-sep') || block.startsWith('<hr')
      const bl = blockLines(block, m.charsPerLine, isSep ? sepExtraLines : 0)
      if (linesUsed + bl > m.linesPerPage && currentBlocks.length > 0) {
        flush()
      }
      currentBlocks.push(block)
      linesUsed += bl
    }

    if (currentBlocks.length > 0 || isFirstOfChapter) flush()
  }

  // ── Page de titre (auto) ──────────────────────────────────────
  const titlePageNum = nextPhys()
  pages.push({
    id: 'auto-title',
    kind: 'auto_title',
    physicalNum: titlePageNum,
    displayNum: '', // pas de folio
    isLiminary: true,
    isVerso: isVerso(titlePageNum),
    showHeader: false,
    headerLeft: '', headerRight: '',
    showFooter: false,
    bigTitle: bookTitle,
    subTitle: authorName,
    blocks: [],
    chapterTitle: '', arcTitle: '', bookTitle, authorName,
  })

  // ── Page de copyright (auto, verso) ──────────────────────────
  const copyrightNum = nextPhys()
  pages.push({
    id: 'auto-copyright',
    kind: 'auto_copyright',
    physicalNum: copyrightNum,
    displayNum: '', // pas de folio
    isLiminary: true,
    isVerso: isVerso(copyrightNum),
    showHeader: false,
    headerLeft: '', headerRight: '',
    showFooter: false,
    bigTitle: undefined,
    subTitle: undefined,
    blocks: [
      `<p>© ${new Date().getFullYear()} ${authorName}</p>`,
      `<p>Tous droits réservés. Toute reproduction, même partielle, est interdite sans autorisation.</p>`,
    ],
    chapterTitle: '', arcTitle: '', bookTitle, authorName,
  })

  // ── Liminaires ────────────────────────────────────────────────
  for (const doc of liminaries) {
    if (isVerso(physNum + 1)) {
      const blankN = nextPhys()
      const blankText = nextText()
      pages.push({
        id: `blank-${blankN}`,
        kind: 'blank',
        physicalNum: blankN,
        displayNum: String(blankText),
        isLiminary: true,
        isVerso: isVerso(blankN),
        showHeader: false, headerLeft: '', headerRight: '',
        showFooter: false,
        blocks: [],
        chapterTitle: '', arcTitle: '', bookTitle, authorName,
      })
    }

    const docN = nextPhys()
    const textN = nextText()
    const html = doc.id ? (documentContents[doc.id] ?? '') : ''
    const blocks = extractBlocks(html)

    pages.push({
      id: `liminary-${doc.type}`,
      kind: 'liminary',
      physicalNum: docN,
      displayNum: String(textN),
      isLiminary: true,
      isVerso: isVerso(docN),
      showHeader: false,
      headerLeft: '', headerRight: '',
      showFooter: settings.footer.show_on_liminaries ?? true,
      bigTitle: doc.title || doc.label,
      docLabel: doc.label,
      blocks,
      chapterTitle: '', arcTitle: '', bookTitle, authorName,
    })
  }

  // ── Table des matières (blocs remplis après la pagination du corps) ──
  const tocN = nextPhys()
  const tocTextN = nextText()
  // Placeholder — les blocs seront mis à jour post-pagination avec les vrais numéros
  const tocPage: BookPage = {
    id: 'auto-toc',
    kind: 'auto_toc',
    physicalNum: tocN,
    displayNum: String(tocTextN),
    isLiminary: true,
    isVerso: isVerso(tocN),
    showHeader: false, headerLeft: '', headerRight: '',
    showFooter: settings.footer.show_on_toc ?? false,
    bigTitle: 'Table des matières',
    blocks: [],
    chapterTitle: '', arcTitle: '', bookTitle, authorName,
  }
  pages.push(tocPage)

  // ── Corps du roman ────────────────────────────────────────────
  let chapterCounter = 0

  for (const arc of arcs) {
    // Page de partie (arc) si activée
    if (settings.structure.part_page) {
      while (isVerso(physNum + 1)) {
        const blankN = nextPhys()
        const blankText = nextText()
        pages.push({
          id: `blank-pre-part-${blankN}`,
          kind: 'blank',
          physicalNum: blankN, displayNum: String(blankText),
          isLiminary: false, isVerso: isVerso(blankN),
          showHeader: false, headerLeft: '', headerRight: '',
          showFooter: false, blocks: [],
          chapterTitle: '', arcTitle: arc.title, bookTitle, authorName,
        })
      }
      const partN = nextPhys()
      const partText = nextText()
      pages.push({
        id: `part-${arc.id}`,
        kind: 'part',
        physicalNum: partN, displayNum: String(partText),
        isLiminary: false, isVerso: isVerso(partN),
        showHeader: false, headerLeft: '', headerRight: '',
        showFooter: settings.footer.show_on_parts ?? false,
        bigTitle: arc.title,
        blocks: [],
        chapterTitle: '', arcTitle: arc.title, bookTitle, authorName,
      })
    }

    for (const chapter of (arc.chapters ?? [])) {
      chapterCounter++

      // Saut de chapitre selon settings
      const targetVerso = settings.structure.chapter_start === 'even'
      const targetRecto = settings.structure.chapter_start === 'odd'

      if (targetRecto) {
        while (isVerso(physNum + 1)) {
          const blankN = nextPhys()
          const blankText = nextText()
          pages.push({
            id: `blank-ch-${blankN}`,
            kind: 'blank',
            physicalNum: blankN, displayNum: String(blankText),
            isLiminary: false, isVerso: isVerso(blankN),
            showHeader: false, headerLeft: '', headerRight: '',
            showFooter: false, blocks: [],
            chapterTitle: chapter.title, arcTitle: arc.title, bookTitle, authorName,
          })
        }
      } else if (targetVerso) {
        while (!isVerso(physNum + 1)) {
          const blankN = nextPhys()
          const blankText = nextText()
          pages.push({
            id: `blank-ch-${blankN}`,
            kind: 'blank',
            physicalNum: blankN, displayNum: String(blankText),
            isLiminary: false, isVerso: isVerso(blankN),
            showHeader: false, headerLeft: '', headerRight: '',
            showFooter: false, blocks: [],
            chapterTitle: chapter.title, arcTitle: arc.title, bookTitle, authorName,
          })
        }
      }

      // Collecte tous les blocs HTML des scènes du chapitre avec séparateurs
      const separator = settings.structure.scene_separator
      const spaceBefore = settings.structure.separator_space_before ?? 1.5
      const spaceAfter  = settings.structure.separator_space_after  ?? 1.0
      const sepStyle = `margin-top:${spaceBefore}em;margin-bottom:${spaceAfter}em`
      const sepHtml =
        separator === 'stars'  ? `<p class="scene-sep" style="${sepStyle}">***</p>` :
        separator === 'dash'   ? `<p class="scene-sep" style="${sepStyle}">—</p>`  :
        separator === 'custom' ? `<p class="scene-sep" style="${sepStyle}">${settings.structure.scene_separator_custom ?? '***'}</p>` :
        ''

      const allBlocks: string[] = []
      const scenes = (chapter.scenes ?? [])
      scenes.forEach((scene, i) => {
        const sceneBlocks = extractBlocks(scene.content ?? '')
        allBlocks.push(...sceneBlocks)
        if (i < scenes.length - 1 && separator !== 'none' && sepHtml) {
          allBlocks.push(sepHtml)
        }
      })

      const displayTitle = applyNumbering(chapter.title, chapterCounter, settings.titles.numbering)
      pushBodyPages(allBlocks, displayTitle, arc.title, true)
    }
  }

  // ── Annexes ───────────────────────────────────────────────────
  for (const doc of annexes) {
    const docN = nextPhys()
    const textN = nextText()
    const display = String(textN)
    const html = doc.id ? (documentContents[doc.id] ?? '') : ''
    const ctx = makeCtx(doc.title || doc.label, '', display)
    pages.push({
      id: `annex-${doc.type}`,
      kind: 'annex',
      physicalNum: docN,
      displayNum: display,
      isLiminary: false,
      isVerso: isVerso(docN),
      showHeader: false,
      headerLeft: resolveField(settings.headers.left_field, ctx),
      headerRight: resolveField(settings.headers.right_field, ctx),
      showFooter: true,
      bigTitle: doc.title || doc.label,
      docLabel: doc.label,
      blocks: extractBlocks(html),
      chapterTitle: doc.title || doc.label, arcTitle: '', bookTitle, authorName,
    })
  }

  // ── Post-passe : remplir le sommaire avec les vrais numéros de page ──
  // Les premières pages de chaque chapitre (bigTitle défini, kind=body) sont dans l'ordre
  const chapterFirstPages = pages.filter(p => p.kind === 'body' && p.bigTitle !== undefined)
  let ci = 0
  tocPage.blocks = arcs.flatMap((arc) => {
    const lines: string[] = []
    if (settings.structure.part_page) {
      lines.push(`<p class="toc-part">${arc.title}</p>`)
    }
    ;(arc.chapters ?? []).forEach((ch) => {
      ci++
      const label = applyNumbering(ch.title, ci, settings.titles.numbering)
      const pageNum = chapterFirstPages[ci - 1]?.displayNum ?? ''
      const numSpan = pageNum ? `<span class="toc-pnum">${pageNum}</span>` : ''
      lines.push(`<p class="toc-chapter"><span class="toc-clabel">${label}</span><span class="toc-leader"></span>${numSpan}</p>`)
    })
    return lines
  })

  return pages
}
