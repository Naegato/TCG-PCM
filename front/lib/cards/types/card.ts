import {
  CardEffect,
  CardRaririty,
  CardSet,
  CardType,
  FoilEffects,
} from "@/constants/card";

export type CardEffectState = {
  effect: CardEffect;
  data: unknown[];
};

export type BasicCard = {
  name: string;
  description: string;
  image: string;
  requiresTarget?: boolean;
  targetType?: number | null;
  rarity: CardRaririty;
  serie: CardSet;
  type?: CardType;
  instanceId: string;
  effects: CardEffectState[];
  isActive: boolean;
  cost?: number;
  hp?: number;
  attack?: number;
  isNewToCollection?: boolean;
  values?: {
    turnRemainingBeforeAction?: number;
    [key: string]: unknown;
  };
};

export type CardLayer = {
  src: string;
  depth: number;
  isIllustration?: boolean;
  alt?: string | null;
  foilEffect?: FoilEffect | null;
  foil?: string | null;
  mask?: string | null;
};

export type CardWithPosition = {
  card: BasicCard;
  rank: number;
  x: number;
  y: number;
  rotation: number;
};

export type FoilEffect = (typeof FoilEffects)[keyof typeof FoilEffects];
