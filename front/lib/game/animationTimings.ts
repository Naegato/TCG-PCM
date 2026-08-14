// Shared between GameCard.tsx (which owns the actual CSS animation + removes the
// className once it's done) and GameContext.tsx (which paces one-by-one event
// playback and needs to know how long each animation takes to *look* finished).
// Keep these in sync with the durations declared in app/globals.css.
export const CARD_TRIGGERED_SHAKE_DURATION = 550;
export const CARD_MARKER_DURATION = 1100;
