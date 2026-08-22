import Image from "@/components/atoms/Image";

type PlayerStatsDisplayProps = {
  money: number;
  health: number;
};

export default function PlayerStatsDisplay({
  money,
  health,
}: PlayerStatsDisplayProps) {
  return (
    <div className="flex flex-col gap-2">
      <div className="flex flex-row items-center gap-2 flex-nowrap bg-white rounded-full border-3 border-ink-outline shadow-[var(--sticker-shadow-sm)] p-2 -rotate-2">
        <Image src="/icons/coins.svg" alt="Money" width={32} height={32} />
        <span
          key={`money-${money}`}
          className="stat-value-change font-display font-extrabold text-xl text-amber-600"
        >
          {money}
        </span>
      </div>
      <div className="flex flex-row items-center gap-2 flex-nowrap bg-white rounded-full border-3 border-ink-outline shadow-[var(--sticker-shadow-sm)] p-2 rotate-2">
        <Image src="/icons/heart.svg" alt="Health" width={32} height={32} />
        <span
          key={`health-${health}`}
          className="stat-value-change font-display font-extrabold text-xl text-cherry"
        >
          {health}
        </span>
      </div>
    </div>
  );
}
