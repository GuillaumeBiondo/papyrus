# Papyrus — Specs Landing Page

---

## Principe général

Une landing page SaaS a un seul objectif : **convertir un visiteur inconnu en inscription**.
Chaque section doit répondre à une question implicite dans la tête du visiteur, dans l'ordre
où ces questions arrivent naturellement. Si une section ne répond à aucune question, elle
n'a pas sa place.

Ordre des questions mentales du visiteur :
1. *C'est quoi ?* → Hero
2. *Ça me concerne ?* → Problème
3. *Comment ça marche ?* → Solution / Fonctionnalités
4. *Est-ce que ça marche vraiment ?* → Preuves / Témoignages
5. *C'est pour moi (roman / scénario / théâtre) ?* → Cas d'usage
6. *Combien ça coûte ?* → Tarifs
7. *Et si j'ai des doutes ?* → FAQ
8. *Je me lance* → CTA final

---

## Structure complète — section par section

---

### 0. Navigation

```
[Logo Papyrus]    Fonctionnalités   Tarifs   Blog   Tutoriels    [Se connecter]  [Commencer gratuitement →]
```

- Logo à gauche, liens au centre, CTAs à droite
- Le CTA principal (`Commencer gratuitement`) en couleur d'accent — il doit être impossible à rater
- `Se connecter` en lien discret (ne pas détourner les nouveaux visiteurs)
- Navigation sticky (reste visible au scroll) — le CTA doit toujours être accessible
- Sur mobile : hamburger menu, CTA principal toujours visible

---

### 1. Hero — *C'est quoi ?*

**L'espace le plus important de la page.** Un visiteur décide en moins de 5 secondes s'il reste ou non. Tout doit être visible sans scroller.

#### Composants du hero

**Titre principal (headline)**
Une seule phrase, bénéfice concret, pas de jargon.

> *L'atelier d'écriture que vous aurez toujours voulu avoir.*

Alternatives à tester :
> *Écrivez mieux. Arrêtez de vous relire en boucle.*
> *L'assistant qui comprend votre roman.*
> *Votre manuscrit mérite mieux que Word.*

Règles : moins de 10 mots, verbe d'action ou bénéfice direct, pas de "solution innovante" ni "plateforme collaborative".

**Sous-titre (subheadline)**
2 phrases max. Répond à "comment ?" et "pour qui ?".

> *Papyrus analyse votre style, détecte vos répétitions et génère des résumés de chapitres automatiquement. Pour les auteurs de romans, scénarios et pièces de théâtre.*

**CTA principal**
> `Commencer gratuitement`

Sous le bouton, une ligne de réassurance en petit :
> *Sans carte bancaire · Annulable à tout moment*

**Visuel**
Screenshot animé (GIF ou vidéo loop 10s) de l'interface en action :
- On voit un texte s'ouvrir dans l'éditeur
- Des soulignements de style apparaissent
- Un panneau latéral montre les suggestions

Ce visuel doit montrer de **vraies données** (pas un Lorem ipsum), idéalement un extrait de genre romanesque reconnaissable.

#### Structure visuelle du hero

```
┌────────────────────────────────────────────────────┐
│                                                    │
│  Titre principal (grand, gras)                     │
│  Sous-titre (plus petit, gris)                     │
│                                                    │
│  [Commencer gratuitement →]                        │
│  Sans carte bancaire · Annulable à tout moment     │
│                                                    │
│              [Screenshot animé de l'app]           │
│                                                    │
└────────────────────────────────────────────────────┘
```

---

### 2. Barre de réassurance — *Suis-je au bon endroit ?*

Juste sous le hero, une ligne horizontale sobre. Deux options selon l'avancement du produit :

**Option A — Chiffres (si tu en as)**
```
  ✦ X auteurs actifs   ·   ✦ X manuscrits en cours   ·   ✦ X corrections suggérées
```

**Option B — Types d'auteurs (si tu démarres)**
```
  Pour les auteurs de :   📖 Romans   🎬 Scénarios   🎭 Pièces de théâtre
```

Cette section ancre l'identité du produit et confirme au visiteur qu'il est au bon endroit.

---

### 3. Problème — *Ça me concerne ?*

**L'erreur classique :** passer directement aux fonctionnalités sans nommer la douleur.
Un visiteur qui se reconnaît dans le problème est 3× plus attentif à la solution.

#### Titre de section
> *Écrire, c'est facile. Se relire, c'est un enfer.*

#### Contenu — 3 douleurs en cards

```
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│ "Je ne vois     │  │ "Je répète les  │  │ "Écrire le      │
│  plus mes       │  │  mêmes mots     │  │  synopsis de    │
│  propres        │  │  sans m'en      │  │  mes chapitres, │
│  erreurs de     │  │  rendre         │  │  c'est une      │
│  style après    │  │  compte."       │  │  corvée."       │
│  100 relectures"│  │                 │  │                 │
└─────────────────┘  └─────────────────┘  └─────────────────┘
```

Ton : empathique, pas condescendant. L'auteur ne doit pas se sentir jugé.

---

### 4. Solution — *Comment ça marche ?*

Pas une liste de fonctionnalités techniques — une transformation.

#### Titre de section
> *Papyrus voit ce que vous ne voyez plus.*

#### Format : 3 étapes visuelles

```
  1                        2                        3
  ─                        ─                        ─
  Collez votre texte   →   Papyrus analyse      →   Améliorez
  ou écrivez               style, rythme,           en un clic
  directement              répétitions

  [Screenshot éditeur]    [Screenshot analyse]    [Screenshot correction]
```

Chaque étape a :
- Un numéro visible
- Un titre court (4 mots max)
- Une phrase d'explication
- Un visuel ou icône

---

### 5. Fonctionnalités — *Qu'est-ce que je peux faire exactement ?*

6 fonctionnalités max. Au-delà, le visiteur se noie. Choisir les 6 qui différencient.

#### Format recommandé : grille 2×3 avec icône + titre + phrase

```
┌──────────────────────┐  ┌──────────────────────┐  ┌──────────────────────┐
│ ✏️  Correction style  │  │ 🔄  Analyse répéti-  │  │ 🤖  Reformulation    │
│                      │  │     tions             │  │     IA               │
│ Détecte les phrases  │  │ Visualise les mots    │  │ Reformulez n'importe │
│ trop longues, les    │  │ et expressions que    │  │ quel passage en      │
│ adverbes en trop,    │  │ vous surutilisez,     │  │ conservant votre     │
│ les tournures faibles│  │ chapitre par chapitre │  │ voix                 │
└──────────────────────┘  └──────────────────────┘  └──────────────────────┘

┌──────────────────────┐  ┌──────────────────────┐  ┌──────────────────────┐
│ 📊  Stats chapitres  │  │ 👥  Équilibre des    │  │ 📄  Multi-formats    │
│                      │  │     personnages       │  │                      │
│ Synopsis auto-généré │  │ Qui parle combien ?   │  │ Roman, scénario,     │
│ et courbe d'évolution│  │ Évolution dans le     │  │ pièce de théâtre :   │
│ de votre narration   │  │ temps de chaque       │  │ chaque format a ses  │
│                      │  │ personnage            │  │ règles propres       │
└──────────────────────┘  └──────────────────────┘  └──────────────────────┘
```

---

### 6. Approfondissement — *Une fonctionnalité en détail*

Choisir **la fonctionnalité la plus différenciante** (celle qu'on ne trouve pas ailleurs) et lui donner une section entière avec un grand visuel.

Candidat : **l'analyse des personnages** — c'est ce que Word, Scrivener et les autres ne font pas.

```
┌────────────────────────────────────────────────────────────┐
│                                                            │
│  "Savoir si votre protagoniste                             │
│   est vraiment au centre de votre histoire."               │
│                                                            │
│  [Grand screenshot du graphique de présence               │
│   des personnages, avec barres colorées par               │
│   chapitre — visuellement fort]                            │
│                                                            │
│  Papyrus calcule le temps de parole et de présence         │
│  de chaque personnage, chapitre par chapitre.              │
│  Idéal pour détecter un personnage qui disparaît           │
│  trop longtemps ou un antagoniste sous-développé.          │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

---

### 7. Témoignages — *Est-ce que ça marche vraiment ?*

**À préparer avant le lancement** : donner des accès gratuits à 5–10 auteurs en échange d'un retour honnête.

#### Format idéal : 3 témoignages en cards

```
┌────────────────────────────────┐
│ ⭐⭐⭐⭐⭐                          │
│                                │
│ "J'ai utilisé Papyrus sur les  │
│  3 derniers chapitres de mon   │
│  roman. Le nombre de fois où   │
│  il a détecté mes tics de      │
│  style que je n'avais jamais   │
│  remarqués... c'est bluffant." │
│                                │
│ — Prénom N., auteur de roman   │
│   [photo si disponible]        │
└────────────────────────────────┘
```

Règles des bons témoignages :
- **Spécifique** : cite une fonctionnalité ou un bénéfice précis (pas "super outil !")
- **Crédible** : prénom + genre littéraire + photo si possible
- **Varié** : un romancier, un scénariste, un auteur de théâtre — un par format cible

Si tu n'as pas encore de témoignages au lancement → utilise des stats d'usage à la place :
> *"X auteurs ont analysé X chapitres ce mois-ci"*

---

### 8. Tarifs — *Combien ça coûte ?*

Section courte sur la landing (renvoi vers `/tarifs` pour les détails).

#### Titre
> *Simple. Transparent.*

#### Format : 2 cards côte à côte

```
┌──────────────────────┐     ┌──────────────────────┐
│       GRATUIT        │     │         PRO          │
│                      │     │                      │
│         0€           │     │   9€ / mois TTC      │
│                      │     │   ou 85€ / an TTC    │
│                      │     │   (2 mois offerts)   │
│ • 1 projet           │     │ • Projets illimités  │
│ • X corrections/mois │     │ • Corrections illim. │
│ • Analyse répétitions│     │ • Toutes les features│
│                      │     │ • Support prioritaire│
│ [Commencer]          │     │ [Essayer 14 jours]   │
└──────────────────────┘     └──────────────────────┘
```

Ligne sous les cards :
> *Pas de surprise. Annulable à tout moment depuis votre espace client.*

---

### 9. FAQ — *Et si j'ai encore des doutes ?*

5–7 questions max, en accordéon (une seule ouverte à la fois).

| Question | Réponse courte |
|---|---|
| Mes textes sont-ils privés ? | Oui, vos manuscrits vous appartiennent. Ils ne sont jamais lus par notre équipe ni utilisés pour entraîner des modèles d'IA. |
| Puis-je annuler à tout moment ? | Oui, sans engagement. L'annulation prend effet à la fin de la période en cours. |
| Papyrus fonctionne-t-il avec mes fichiers Word ? | Vous pouvez coller votre texte directement ou importer un fichier .docx. |
| Y a-t-il une limite de longueur de manuscrit ? | Non, il n'y a pas de limite sur la taille de vos projets. |
| Que se passe-t-il si je dépasse mon quota gratuit ? | Vous recevez une notification. Vous gardez l'accès en lecture à vos projets. |
| Proposez-vous des réductions ? | Des codes promo sont parfois disponibles. Inscrivez-vous à notre newsletter pour les recevoir. |

---

### 10. CTA final — *Dernière chance de convaincre*

Le visiteur qui arrive ici a tout lu. Il est intéressé mais n'a pas encore cliqué. Il faut reformuler la valeur en une phrase et remettre le bouton.

```
┌────────────────────────────────────────────────────┐
│                                                    │
│   Votre prochain chapitre mérite                   │
│   d'être votre meilleur.                           │
│                                                    │
│         [Commencer gratuitement →]                 │
│     Sans carte bancaire · 2 minutes pour démarrer  │
│                                                    │
└────────────────────────────────────────────────────┘
```

Fond de couleur différent (sombre ou couleur d'accent) pour marquer la rupture visuelle.

---

### 11. Footer

```
┌────────────────────────────────────────────────────────────────┐
│  [Logo Papyrus]                                                │
│  L'outil d'écriture pour auteurs sérieux.                      │
│                                                                │
│  Produit          Ressources         Légal                     │
│  Fonctionnalités  Blog               Mentions légales          │
│  Tarifs           Tutoriels          CGU / CGV                 │
│  Changelog        À propos           Politique de conf.        │
│                                      Cookies                   │
│                                                                │
│  © 2026 Papyrus · Fait avec ☕ en France · privacy@papyrus.io  │
└────────────────────────────────────────────────────────────────┘
```

---

## Règles de copywriting à respecter

**Ce qu'on dit**
- Bénéfice avant fonctionnalité : *"Voyez d'un coup d'œil si vos personnages sont équilibrés"* plutôt que *"Statistiques de présence des personnages"*
- Ton : direct, chaleureux, pas corporate. Papyrus s'adresse à des créatifs.
- "Vous" (pas "tu" pour le site public — plus professionnel, plus inclusif)
- Phrases courtes. Jamais deux adjectifs là où un suffit.

**Ce qu'on évite**
- "Solution innovante", "plateforme", "écosystème", "synergies"
- "Simple et intuitif" (tout le monde le dit, personne ne le croit)
- Trop de superlatifs : "le meilleur outil", "révolutionnaire"
- Expliquer comment ça marche techniquement (l'utilisateur s'en fiche)

---

## Tests A/B à prévoir au lancement

Ne pas tester plusieurs choses en même temps. Une variable à la fois.

| Priorité | Élément à tester | Variante A | Variante B |
|---|---|---|---|
| 1 | Headline hero | *L'atelier d'écriture que vous aurez toujours voulu avoir* | *Écrivez mieux. Arrêtez de vous relire en boucle.* |
| 2 | CTA principal | *Commencer gratuitement* | *Essayer gratuitement 14 jours* |
| 3 | Visuel hero | Screenshot statique | GIF animé |
| 4 | Ordre sections | Problème → Solution → Features | Features → Problème → Solution |

Outil : Plausible a des fonctions basiques d'A/B ou on peut le faire manuellement avec deux URLs + redirect aléatoire.

---

## Métriques à suivre

| Métrique | Définition | Objectif initial |
|---|---|---|
| Taux de rebond | % visiteurs qui repartent sans cliquer | < 60% |
| Taux de clic CTA hero | % visiteurs qui cliquent sur le CTA principal | > 5% |
| Taux de conversion | % visiteurs qui créent un compte | > 2% |
| Scroll depth | % visiteurs qui atteignent la section Tarifs | > 40% |

Ces chiffres sont des ordres de grandeur pour un SaaS B2C en lancement — pas des vérités absolues.
