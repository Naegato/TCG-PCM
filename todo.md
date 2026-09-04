# TODO - Inventaire (chargement des cartes)

- [ ] Réduire le coût de rendu des cartes (multi-layers + overlays + `use-fit-text`).
- [ ] Éviter le préchargement d’images en double (`new Image()` + rendu Next/Image).
- [ ] Optimiser `/inventory/collection` (tri et accès templates trop coûteux).
- [ ] Supprimer le sur-fetching au chargement (ne pas charger decks/limits si onglet cartes).
- [ ] Passer les appels initiaux en parallèle au lieu du séquentiel.
- [ ] Ajouter une vraie virtualisation/pagination de la grille de cartes.

## Texte (lisibilité + performance)

- [ ] Définir une stratégie texte par zone (titre, stats, description, type, rareté).
- [ ] Limiter `use-fit-text` aux cartes visibles/zoomées.
- [ ] Basculer vers tailles fixes + fallback plutôt qu’un fitting systématique.
- [ ] Réduire les resets de readiness/recalculs quand seul le non-texte change.
- [ ] Uniformiser les règles d’overflow (max lignes, ellipsis, interlignage).
- [ ] Centraliser et corriger les labels FR (accents, orthographe, cohérence).
- [ ] Évaluer une approche “pretext” (pré-calcul/pré-rendu du texte) pour alléger le client.
