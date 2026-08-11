import { CardEffect } from "@/constants/card";

type CardEffectBadgeProps = {
  effect: CardEffect;
  label: string;
  description?: string;
  size?: "sm" | "lg";
  className?: string;
};

const SIZE_STYLES: Record<"sm" | "lg", string> = {
  sm: "px-1.5 py-0.5 text-[10px]",
  lg: "px-3 py-1 text-sm",
};

const EFFECT_BADGE_STYLES: Record<CardEffect, string> = {
  [CardEffect.HACKED]: "border-violet-300/70 bg-violet-100 text-violet-900",
  [CardEffect.TORNED]: "border-orange-300/70 bg-orange-100 text-orange-900",
  [CardEffect.POWER_BOOST]: "border-rose-300/70 bg-rose-100 text-rose-900",
};

const EFFECT_BADGE_ICONS: Record<CardEffect, string> = {
  [CardEffect.HACKED]: "⚡",
  [CardEffect.TORNED]: "🌀",
  [CardEffect.POWER_BOOST]: "💪",
};

export default function CardEffectBadge({
  effect,
  label,
  description,
  size = "sm",
  className,
}: CardEffectBadgeProps) {
  return (
    <span
      title={description}
      className={`inline-flex items-center gap-0.5 rounded-full border shadow-sm font-extrabold leading-none whitespace-nowrap ${
        SIZE_STYLES[size]
      } ${
        EFFECT_BADGE_STYLES[effect] ?? "border-zinc-300/70 bg-zinc-100 text-zinc-900"
      } ${className ?? ""}`}
    >
      <span aria-hidden="true">{EFFECT_BADGE_ICONS[effect] ?? "✨"}</span>
      {label}
    </span>
  );
}
