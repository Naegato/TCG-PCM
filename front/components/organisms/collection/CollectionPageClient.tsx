"use client";

import { useState } from "react";
import { CardCollectionEntry } from "@/app/types/collection";
import { Deck, DeckLimits } from "@/app/types/deck";
import InventoryTabs from "@/components/organisms/inventory/InventoryTabs";
import InventoryCardsPanel from "@/components/organisms/inventory/InventoryCardsPanel";
import InventoryDecksPanel from "@/components/organisms/inventory/InventoryDecksPanel";

type InventoryFilters = {
  search?: string;
  set?: string;
  rarity?: string;
  type?: string;
};

type CollectionPageClientProps = {
  entries: CardCollectionEntry[];
  decks: Deck[];
  deckLimits: DeckLimits;
  initialTab?: "cards" | "decks";
  initialFilters?: InventoryFilters;
};

export default function CollectionPageClient({
  entries,
  decks,
  deckLimits,
  initialTab = "cards",
  initialFilters,
}: CollectionPageClientProps) {
  const [activeTab, setActiveTab] = useState<"cards" | "decks">(initialTab);

  return (
    <div className="mx-2 my-4 sm:mx-4">
      <div className="ml-1 inline-flex z-20">
        <InventoryTabs value={activeTab} onChange={setActiveTab} />
      </div>

      <div className="flex flex-col gap-6 rounded-tr-3xl rounded-b-3xl border-2 border-ink-outline bg-card p-6 shadow-[var(--sticker-shadow-lg)]">
        {activeTab === "cards" ? (
          <InventoryCardsPanel entries={entries} initialFilters={initialFilters} />
        ) : null}

        {activeTab === "decks" ? (
          <InventoryDecksPanel
            decks={decks}
            entries={entries}
            limits={deckLimits}
          />
        ) : null}
      </div>
    </div>
  );
}
