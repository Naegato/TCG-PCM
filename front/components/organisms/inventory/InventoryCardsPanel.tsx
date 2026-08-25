"use client";

import { useEffect, useMemo, useState } from "react";
import { usePathname } from "next/navigation";
import { AiOutlineLock } from "react-icons/ai";
import { MdFilterAltOff } from "react-icons/md";

import type { CardCollectionEntry } from "@/app/types/collection";
import { CardRaririty, CardSet, CardSize, CardType } from "@/constants/card";
import type { BasicCard } from "@/lib/cards/types/card";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import Card from "@/components/molecules/Card";
import CardWithZoom from "@/components/organisms/card/CardWithZoom";
import CardQuantityBadge from "@/components/atoms/collection/CardQuantityBadge";
import LazyMount from "@/components/atoms/LazyMount";

type InventoryCardsPanelProps = {
  entries: CardCollectionEntry[];
  initialFilters?: {
    search?: string;
    set?: string;
    rarity?: string;
    type?: string;
  };
};

function parseEnumFilter<T extends string>(
  value: string | undefined,
  allowedValues: readonly T[],
): T | typeof ALL {
  if (value && (allowedValues as readonly string[]).includes(value)) {
    return value as T;
  }
  return ALL;
}

const SET_LABELS: Record<CardSet, string> = {
  [CardSet.ORIGINAL]: "Original",
  [CardSet.TBOI]: "The Binding of Isaac",
  [CardSet.BTD6]: "Bloons TD 6",
};

const RARITY_LABELS: Record<CardRaririty, string> = {
  [CardRaririty.COMMON]: "Commune",
  [CardRaririty.UNCOMMON]: "Peu commune",
  [CardRaririty.RARE]: "Rare",
  [CardRaririty.EPIC]: "Épique",
  [CardRaririty.LEGENDARY]: "Légendaire",
};

const TYPE_LABELS: Record<CardType, string> = {
  [CardType.CHARACTER]: "Personnage",
  [CardType.MONSTER]: "Monstre",
  [CardType.PASSIVE]: "Passif",
  [CardType.CONSUMABLE]: "Consommable",
};

const ALL = "ALL" as const;

export default function InventoryCardsPanel({
  entries,
  initialFilters,
}: InventoryCardsPanelProps) {
  const pathname = usePathname();
  const [search, setSearch] = useState(initialFilters?.search?.trim() ?? "");
  const [setFilter, setSetFilter] = useState<CardSet | typeof ALL>(() =>
    parseEnumFilter(initialFilters?.set, Object.values(CardSet)),
  );
  const [rarityFilter, setRarityFilter] = useState<CardRaririty | typeof ALL>(
    () => parseEnumFilter(initialFilters?.rarity, Object.values(CardRaririty)),
  );
  const [typeFilter, setTypeFilter] = useState<CardType | typeof ALL>(() =>
    parseEnumFilter(initialFilters?.type, Object.values(CardType)),
  );

  const hasActiveFilters =
    search.trim() !== "" ||
    setFilter !== ALL ||
    rarityFilter !== ALL ||
    typeFilter !== ALL;

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const filterValues: Record<string, string> = {
      q: search.trim(),
      set: setFilter === ALL ? "" : setFilter,
      rarity: rarityFilter === ALL ? "" : rarityFilter,
      type: typeFilter === ALL ? "" : typeFilter,
    };

    Object.entries(filterValues).forEach(([key, value]) => {
      if (value) {
        params.set(key, value);
      } else {
        params.delete(key);
      }
    });

    const query = params.toString();
    window.history.replaceState(null, "", query ? `${pathname}?${query}` : pathname);
  }, [search, setFilter, rarityFilter, typeFilter, pathname]);

  const resetFilters = () => {
    setSearch("");
    setSetFilter(ALL);
    setRarityFilter(ALL);
    setTypeFilter(ALL);
  };

  const filteredEntries = useMemo(() => {
    const normalizedSearch = search.trim().toLowerCase();

    return entries.filter(({ card }) => {
      if (
        normalizedSearch &&
        !card.name.toLowerCase().includes(normalizedSearch)
      ) {
        return false;
      }
      if (setFilter !== ALL && card.serie !== setFilter) {
        return false;
      }
      if (rarityFilter !== ALL && card.rarity !== rarityFilter) {
        return false;
      }
      if (typeFilter !== ALL && card.type !== typeFilter) {
        return false;
      }
      return true;
    });
  }, [entries, search, setFilter, rarityFilter, typeFilter]);

  const unlockedFilteredCount = useMemo(
    () => filteredEntries.filter(({ quantity }) => quantity > 0).length,
    [filteredEntries],
  );

  return (
    <>
      <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
        <Input
          placeholder="Rechercher une carte..."
          value={search}
          onChange={(event) => setSearch(event.target.value)}
          className="sm:max-w-64"
        />

        <select
          value={setFilter}
          onChange={(event) =>
            setSetFilter(event.target.value as CardSet | typeof ALL)
          }
          className="h-9 rounded-xl border-2 border-ink-outline bg-background px-2.5 text-sm outline-none focus-visible:ring-3 focus-visible:ring-primary/35"
        >
          <option value={ALL}>Tous les sets</option>
          {Object.values(CardSet).map((set) => (
            <option key={set} value={set}>
              {SET_LABELS[set]}
            </option>
          ))}
        </select>

        <select
          value={rarityFilter}
          onChange={(event) =>
            setRarityFilter(event.target.value as CardRaririty | typeof ALL)
          }
          className="h-9 rounded-xl border-2 border-ink-outline bg-background px-2.5 text-sm outline-none focus-visible:ring-3 focus-visible:ring-primary/35"
        >
          <option value={ALL}>Toutes les raretés</option>
          {Object.values(CardRaririty).map((rarity) => (
            <option key={rarity} value={rarity}>
              {RARITY_LABELS[rarity]}
            </option>
          ))}
        </select>

        <select
          value={typeFilter}
          onChange={(event) =>
            setTypeFilter(event.target.value as CardType | typeof ALL)
          }
          className="h-9 rounded-xl border-2 border-ink-outline bg-background px-2.5 text-sm outline-none focus-visible:ring-3 focus-visible:ring-primary/35"
        >
          <option value={ALL}>Tous les types</option>
          {Object.values(CardType).map((type) => (
            <option key={type} value={type}>
              {TYPE_LABELS[type]}
            </option>
          ))}
        </select>

        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={resetFilters}
          disabled={!hasActiveFilters}
        >
          <MdFilterAltOff /> Réinitialiser les filtres
        </Button>

        <p className="text-sm text-muted-foreground sm:ml-auto">
          {unlockedFilteredCount} / {filteredEntries.length} cartes debloquées
        </p>
      </div>

      {0 === filteredEntries.length ? (
        <p className="text-center text-muted-foreground">
          Aucune carte ne correspond à ces filtres.
        </p>
      ) : (
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
          {filteredEntries.map(({ card, quantity }) => {
            const displayedCard: BasicCard = {
              ...card,
              isActive: true,
              effects: [],
            };
            const isOwned = quantity > 0;

            return (
              <div
                key={card.instanceId}
                className="relative flex justify-center"
              >
                <LazyMount placeholderClassName="aspect-card w-card-md">
                  {isOwned ? (
                    <>
                      <div className="transform-gpu transition-transform duration-200 ease-out hover:z-10 hover:scale-110">
                        <CardWithZoom
                          card={displayedCard}
                          size={CardSize.MD}
                          zoomOnSingleClick
                        />
                      </div>
                      <CardQuantityBadge
                        quantity={quantity}
                        className="absolute -top-2 -right-2"
                      />
                    </>
                  ) : (
                    <div className="relative grayscale opacity-60 pointer-events-none select-none">
                      <Card card={displayedCard} size={CardSize.MD} />
                      <div className="absolute inset-0 flex items-center justify-center">
                        <AiOutlineLock className="h-8 w-8 text-white drop-shadow" />
                      </div>
                    </div>
                  )}
                </LazyMount>
              </div>
            );
          })}
        </div>
      )}
    </>
  );
}
