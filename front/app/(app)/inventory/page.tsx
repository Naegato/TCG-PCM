import { authApiGet } from "@/lib/api/authServer";
import CollectionPageClient from "@/components/organisms/collection/CollectionPageClient";
import { CardCollectionResponse } from "@/app/types/collection";
import {
  DeckCollectionResponse,
  DeckLimits,
  normalizeDeckCollection,
} from "@/app/types/deck";

type InventoryPageProps = {
  searchParams: Promise<{
    tab?: string;
    q?: string;
    set?: string;
    rarity?: string;
    type?: string;
  }>;
};

export default async function InventoryPage({
  searchParams,
}: InventoryPageProps) {
  const { entries } = await authApiGet<CardCollectionResponse>(
    "/inventory/collection",
  );
  const decksResponse = await authApiGet<DeckCollectionResponse>("/decks");
  const deckLimits = await authApiGet<DeckLimits>("/decks/limits");
  const decks = normalizeDeckCollection(decksResponse);
  const { tab, q, set, rarity, type } = await searchParams;

  return (
    <CollectionPageClient
      entries={entries}
      decks={decks}
      deckLimits={deckLimits}
      initialTab={tab === "decks" ? "decks" : "cards"}
      initialFilters={{ search: q, set, rarity, type }}
    />
  );
}
