// Shared between the component that owns an animation (which runs the CSS and clears its own
// state once done) and GameContext.tsx (which paces one-by-one event playback and needs to know
// how long each animation takes to *look* finished, without importing the component itself —
// GameAnnouncements already imports GameContext for targeting state, so the reverse import
// would be circular). Keep these in sync with the durations declared in app/globals.css.
export const CARD_TRIGGERED_SHAKE_DURATION = 550;
export const CARD_MARKER_DURATION = 1100;

// Consumable giant reveal: a run of random numbers (COUNTDOWN_BEAT_MS per digit) before the
// card flashes/shakes in, a beat of settle time, then a second smaller burst of random numbers
// over the revealed card — see ConsumableCardReveal in GameAnnouncements.tsx.
export const COUNTDOWN_BEAT_MS = 350;
export const COUNTDOWN_DIGIT_COUNT = 8;
export const COUNTDOWN_AFTER_DIGIT_COUNT = 5;
export const COUNTDOWN_CARD_SETTLE_MS = 1200;
export const CONSUMABLE_REVEAL_DURATION_MS =
  COUNTDOWN_DIGIT_COUNT * COUNTDOWN_BEAT_MS +
  COUNTDOWN_CARD_SETTLE_MS +
  COUNTDOWN_AFTER_DIGIT_COUNT * COUNTDOWN_BEAT_MS +
  600;
