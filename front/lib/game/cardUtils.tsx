import {
  createElement,
  type FocusEvent,
  type MouseEvent,
  type ReactNode,
  useState,
} from "react";
import { createPortal } from "react-dom";

type CardDescriptionData = {
  name?: string;
  description?: string;
  hp?: number;
  attack?: number;
  cost?: number;
};

type EffectDescriptionData = {
  name: string;
  description: string;
};

type DescriptionLookupData = {
  cards?: Record<string, CardDescriptionData>;
  cardEffects?: Record<string, EffectDescriptionData>;
};

type TooltipPosition = {
  x: number;
  y: number;
};

const TAG_TOOLTIP_OFFSET = 12;
const TAG_TOOLTIP_MAX_WIDTH = 280;

const getFocusPosition = (
  event: FocusEvent<HTMLSpanElement>,
): TooltipPosition => {
  const rect = event.currentTarget.getBoundingClientRect();

  return {
    x: rect.left + TAG_TOOLTIP_OFFSET,
    y: rect.bottom + TAG_TOOLTIP_OFFSET,
  };
};

const RichTag = ({
  content,
  className,
  tooltipText,
}: {
  content: string;
  className: string;
  tooltipText?: string;
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const [position, setPosition] = useState<TooltipPosition | null>(null);

  const updatePosition = (event: MouseEvent<HTMLSpanElement>) => {
    setPosition({
      x: event.clientX + TAG_TOOLTIP_OFFSET,
      y: event.clientY + TAG_TOOLTIP_OFFSET,
    });
  };

  const shouldRenderTooltip = isOpen && !!tooltipText && position;

  return (
    <>
      <span
        className={className}
        onMouseEnter={(event) => {
          updatePosition(event);
          setIsOpen(true);
        }}
        onMouseMove={updatePosition}
        onMouseLeave={() => setIsOpen(false)}
        onFocus={(event) => {
          setPosition(getFocusPosition(event));
          setIsOpen(true);
        }}
        onBlur={() => setIsOpen(false)}
        tabIndex={tooltipText ? 0 : -1}
      >
        {content}
      </span>
      {shouldRenderTooltip && typeof document !== "undefined"
        ? createPortal(
            <div
              className="pointer-events-none fixed z-[9999] rounded-xl border border-white/25 bg-black/90 px-3 py-2 text-left text-xs text-white shadow-lg"
              style={{
                left: position.x,
                top: position.y,
                maxWidth: TAG_TOOLTIP_MAX_WIDTH,
                whiteSpace: "pre-line",
              }}
            >
              {tooltipText}
            </div>,
            document.body,
          )
        : null}
    </>
  );
};

const createTagHelper =
  (
    className: string,
    getHoverText?: (
      content: string,
      lookupData?: DescriptionLookupData | null,
    ) => string | undefined,
  ) =>
  (
    content: string,
    key: number,
    lookupData?: DescriptionLookupData | null,
  ): ReactNode =>
    createElement(RichTag, {
      key,
      content,
      className,
      tooltipText: getHoverText?.(content, lookupData),
    });

const getCardHoverText = (
  content: string,
  lookupData?: DescriptionLookupData | null,
): string => {
  const cardId = content.trim();
  const card = lookupData?.cards?.[cardId];

  if (!card) {
    return `Carte: ${cardId}`;
  }

  const title = card.name ?? cardId;
  const description = card.description ?? "";
  const stats = [
    card.cost !== undefined ? `Coût: ${card.cost}` : null,
    card.attack !== undefined ? `ATK: ${card.attack}` : null,
    card.hp !== undefined ? `PV: ${card.hp}` : null,
  ]
    .filter((value): value is string => value !== null)
    .join(" • ");

  if (description && stats) {
    return `${title}\n${description}\n${stats}`;
  }

  if (description) {
    return `${title}\n${description}`;
  }

  if (stats) {
    return `${title}\n${stats}`;
  }

  return title;
};

const getEffectHoverText = (
  content: string,
  lookupData?: DescriptionLookupData | null,
): string => {
  const effectId = content.trim();
  const effect = lookupData?.cardEffects?.[effectId];

  if (!effect) {
    return `Effet: ${effectId}`;
  }

  return `${effect.name}\n${effect.description}`;
};

const valueTag = createTagHelper("font-bold text-amber-300");
const effectTag = createTagHelper(
  "font-bold text-sky-300 underline decoration-dotted cursor-help",
  getEffectHoverText,
);
const constTag = createTagHelper("font-bold text-zinc-200");
const cardTag = createTagHelper(
  "font-bold text-fuchsia-300 underline decoration-dotted cursor-help",
  getCardHoverText,
);
const setTag = createTagHelper("font-bold text-emerald-300");

const markupMapping: Record<
  string,
  (
    content: string,
    key: number,
    lookupData?: DescriptionLookupData | null,
  ) => ReactNode
> = {
  value: valueTag,
  effect: effectTag,
  const: constTag,
  card: cardTag,
  set: setTag,
};

export const convertDescriptions = (
  description: string,
  lookupData?: DescriptionLookupData | null,
): ReactNode[] => {
  const regex = /<(\w+)>(.*?)<\/\1>/g;

  const result: ReactNode[] = [];
  let lastIndex = 0;
  let match;
  let key = 0;

  while ((match = regex.exec(description)) !== null) {
    const [fullMatch, tag, content] = match;

    if (match.index > lastIndex) {
      result.push(description.slice(lastIndex, match.index));
    }

    if (markupMapping[tag]) {
      result.push(markupMapping[tag](content, key++, lookupData));
    } else {
      result.push(content);
    }

    lastIndex = match.index + fullMatch.length;
  }

  if (lastIndex < description.length) {
    result.push(description.slice(lastIndex));
  }

  return result;
};
