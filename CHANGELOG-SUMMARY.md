# Récapitulatif des nouveautés — Papyrus

> Dernière mise à jour : 9 mai 2026

---

## 🔒 Blocage de compte utilisateur *(PR #7 — 8 mai 2026)*

Les administrateurs peuvent désormais **suspendre temporairement un compte** directement depuis l'interface d'administration.

- Un toggle rouge dans le tableau des utilisateurs permet de bloquer/débloquer un compte en un clic.
- Une raison de blocage (optionnelle) peut être renseignée via un formulaire en ligne.
- Un compte bloqué **ne peut plus se connecter ni accéder à l'application**, et reçoit un message d'erreur explicite sur la page de connexion.

---

## 🛠️ Mode maintenance avec popup et bypass *(PR #6 — 8 mai 2026)*

Un **système de maintenance** complet a été introduit pour informer les utilisateurs lors des opérations de mise à jour.

- Une **popup de blocage** s'affiche automatiquement lorsque la maintenance est active.
- Une **bannière d'avertissement** prévient les utilisateurs d'une maintenance à venir.
- La maintenance peut être déclenchée manuellement ou **programmée** (date/heure de début et de fin) depuis la page Paramètres.
- Les **administrateurs** sont toujours exemptés. Un toggle dans la page Utilisateurs permet d'accorder le bypass à des comptes spécifiques.

---

## 🎨 Couleurs d'interface personnalisables *(PR #5 — 8 mai 2026)*

L'interface de Papyrus est maintenant **davantage personnalisable** depuis la page Paramètres.

- La **couleur d'accentuation** (couleur de l'interface) s'applique désormais aussi aux en-têtes des cartes du tableau de bord, en respectant le mode clair/sombre.
- Les boutons d'action dans l'arborescence (arcs, chapitres, scènes) adoptent la couleur brand pour un meilleur contraste visuel.
- Nouveau réglage **"Fond des panneaux (mode sombre)"** : 4 presets (Profond / Moyen / Neutre / Doux) pour ajuster la profondeur des barres latérales en mode sombre.

---

## ✍️ Mantra personnel en filigrane dans l'éditeur *(PR #4 — 8 mai 2026)*

Les utilisateurs peuvent définir un **mantra d'écriture** qui s'affiche en filigrane lorsqu'aucune scène n'est ouverte.

- Le mantra se configure dans la nouvelle section **"Écriture"** des paramètres (jusqu'à 120 caractères).
- L'état vide de l'éditeur a été repensé : le titre du projet est ancré en haut, le mantra est centré au milieu de l'écran.
- L'ancien message générique « Sélectionne une scène » disparaît au profit de ce message personnalisé.

---

## 📋 Nouveau sélecteur de statut pour les scènes *(PR #3 — 8 mai 2026)*

La gestion du **statut des scènes** a été revue pour être plus claire et plus intuitive.

- Les anciens boutons I/B/R/F dans la barre d'outils sont remplacés par un **menu déroulant** affichant le statut courant (icône + nom) avec une description au survol.
- L'arborescence affiche désormais des **icônes SVG significatives** à la place des points colorés :
  - 💡 Idée
  - ✏️ Brouillon
  - 🔄 Révisé
  - ✅ Final
- Le statut par défaut des nouvelles scènes passe de *Idée* à **Brouillon**.
