# Papyrus — Specs Marketing Site

> Brainstorm initial — à affiner

---

## Vision

Papyrus est une application d'écriture assistée pour auteurs. Le cœur, c'est l'app. Mais comme tout bon outil SaaS, il lui faut un espace public séparé qui joue plusieurs rôles à la fois :

- **Vitrine** : convaincre un visiteur inconnu en moins de 10 secondes
- **Acquisition** : attirer du trafic organique (SEO, Instagram, bouche à oreille)
- **Éducation** : montrer comment on utilise l'outil, inspirer les auteurs
- **Conversion** : transformer le visiteur en compte payant
- **Lien** : rediriger vers l'app et en assurer la continuité

---

## Les 5 espaces du site

### 1. Page d'accueil (Landing page)

La porte d'entrée. Elle doit répondre à une seule question : *"Pourquoi Papyrus plutôt que Word ?"*

**Sections envisagées :**
- Hero : accroche forte + CTA "Essayer gratuitement" + visuel de l'interface
- Problème → Solution : l'auteur qui galère vs l'auteur avec Papyrus
- Fonctionnalités clés (6 max, avec icône + phrase) :
  - Correcteur orthographique et stylistique
  - Détection des répétitions
  - Reformulation assistée
  - Analyse des chapitres (synopsis auto, statistiques)
  - Ratios de présence des personnages
  - Support roman / théâtre / scénario
- Proof : témoignages, nombre d'auteurs, extraits de textes améliorés
- CTA final : créer un compte

**Questions ouvertes :**
- Faut-il une démo interactive ou un screenshot tour ?
- Quel est le bénéfice numéro 1 à mettre en avant ? (gain de temps ? qualité du texte ? structure ?)

---

### 2. Blog / Contenu

L'espace qui donne de la valeur sans friction — et qui peut vivre sur Instagram en parallèle.

**Angle éditorial :**
- Conseils de craft d'écriture (structure narrative, personnages, rythme)
- Coulisses de Papyrus (comment une fonctionnalité a été conçue)
- Interviews d'auteurs
- Fiches pratiques (comment écrire un synopsis, comment gérer l'acte 2, etc.)

**Format dual Instagram/Blog :**
- Article long sur le site (800–1500 mots, bon pour le SEO)
- Version condensée "carousel" ou "caption" prête pour Instagram (3–5 points clés)
- Idée : chaque article a un champ "version Instagram" dans son frontmatter

**Stack blog :**

| Option | Avantages | Inconvénients |
|---|---|---|
| Markdown local (ex: Astro Content Collections) | Simple, dans le repo, versioning git | Écrire en markdown pas naturel pour tout le monde |
| CMS headless (Notion, Sanity, Contentful) | Interface agréable, collaboration | Complexité d'intégration, coût |
| Notion comme CMS | Tu écris dans Notion, API sync vers le site | API Notion fragile, pagination complexe |

**Recommandation provisoire :** Markdown local avec un frontmatter bien structuré, et un script d'export Instagram. Si le volume d'articles augmente → migrer vers un CMS.

---

### 3. Tarifs & Souscription

La page qui convertit. Elle doit être claire, honnête, et lever les objections.

**Structure de prix à définir (options) :**

```
Option A — Freemium
  - Gratuit : X projets, X corrections/mois
  - Pro : illimité + fonctionnalités avancées (analyse chapitres, stats)
  - (Futur) Équipe : partage de projets

Option B — Essai gratuit 14 jours → abonnement
  - Un seul plan Pro (mensuel / annuel avec réduction)
  - Pas de version gratuite permanente

Option C — Pay-as-you-go
  - Paiement à l'usage (crédits d'analyse)
  - Adapté si usage irrégulier des auteurs
```

**Questions à trancher :**
- Quel est le prix cible ? (10€/mois ? 5€/mois ? 15€/mois ?)
- Freemium ou trial only ? (freemium = plus d'acquisition, trial = plus simple à gérer)
- L'inscription se fait sur le marketing site ou directement dans l'app ?

**Éléments de la page Tarifs :**
- Tableau comparatif des plans
- FAQ (annulation, données, compatibilité)
- Garantie (ex: satisfait ou remboursé 30 jours)
- Témoignage court d'un auteur ayant souscrit

---

### 4. Tutoriels

L'espace qui réduit le "time to value" — l'auteur doit réussir quelque chose en moins de 5 minutes.

**Types de contenu :**
- Guides de démarrage ("Créer mon premier projet")
- Guides par fonctionnalité ("Comment utiliser le correcteur de style")
- Guides par format ("Écrire un scénario avec Papyrus")

**Format :**
- Texte + screenshots annotés (rapide à produire)
- Vidéos courtes < 3 min (fort impact, plus lourd à maintenir)
- GIFs pour les interactions clés

**Structure proposée :**
```
Tutoriels/
  Démarrage rapide
  Fonctionnalités/
    Correction orthographique
    Correction de style
    Analyse des répétitions
    Reformulation
    Analyse de chapitres
    Statistiques personnages
  Par format/
    Roman
    Pièce de théâtre
    Scénario
```

---

### 5. Création de compte / Portail

Le pont entre le site public et l'app.

**Flux utilisateur :**
```
Landing page
  → CTA "Commencer gratuitement"
    → Page inscription (email + mot de passe)
      → Email de confirmation
        → Onboarding dans l'app (choix du type de projet)
```

**Questions :**
- Auth gérée par le backend Papyrus existant ou service externe (Clerk, Auth0) ?
- Page "Se connecter" sur le marketing site ou redirect vers l'app ?
- Gestion des abonnements : Stripe intégré directement ?

---

## Architecture technique

### Deux approches possibles

**Option A — Site séparé (recommandé)**
- Un repo (ou sous-dossier) dédié au site marketing
- Stack : **Astro** (SSG, excellent pour SEO + blog markdown) ou **Nuxt** (cohérent avec Vue.js de l'app)
- Domaine : `papyrus.io` (marketing) + `app.papyrus.io` (application)
- Indépendant : peut être déployé et mis à jour sans toucher à l'app

**Option B — Intégré dans le repo Papyrus**
- Pages publiques ajoutées au frontend Vue.js existant
- Moins de maintenance de repo, mais couplage fort
- Déploiement unique mais risque de ralentir le site public

**Recommandation :** Option A avec **Nuxt** pour rester dans l'écosystème Vue.js déjà maîtrisé, générer du HTML statique pour le SEO, et partager éventuellement un design system.

---

## Workflow contenu Instagram

Pour chaque article de blog :

1. Rédiger l'article complet en markdown (site)
2. Extraire les 5 points clés → texte du carousel Instagram
3. Créer le visuel (Canva, Figma template) avec la "une" de l'article
4. Publier dans l'ordre : site d'abord → Instagram 24h après (évite le contenu dupliqué)

**Frontmatter d'article (proposition) :**
```yaml
---
title: "Comment structurer l'acte 2 de votre roman"
date: 2026-06-01
category: craft
tags: [structure, roman, acte]
excerpt: "L'acte 2 est le cimetière de tous les romans non terminés. Voici comment s'en sortir."
instagram_version: |
  5 conseils pour survivre à l'acte 2 :
  1. ...
  2. ...
  3. ...
  4. ...
  5. ...
published: true
---
```

---

## Ce qu'il faut décider avant de commencer

| Décision | Options | Urgence |
|---|---|---|
| Modèle de pricing | Freemium / Trial / Pay-as-you-go | Haute |
| Stack technique | Astro / Nuxt / intégré | Haute |
| CMS ou markdown | Local / Notion / Sanity | Moyenne |
| Domaine | papyrus.io / autre | Haute |
| Passerelle auth | Backend existant / Clerk / Auth0 | Moyenne |
| Gestion paiements | Stripe / Paddle / autre | Haute |

---

## Prochaines étapes suggérées

1. **Trancher le modèle de pricing** — tout découle de là
2. **Choisir la stack** (Nuxt recommandé pour la cohérence)
3. **Définir l'accroche principale** de la landing page (1 phrase, 1 bénéfice)
4. **Créer un template d'article** et rédiger le premier post
5. **Intégrer Stripe** dans le backend existant (ou valider que c'est déjà prévu)
6. **Wireframes** des 3 pages principales (landing, tarifs, tutos)
