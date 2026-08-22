import Card from "@/components/molecules/Card";
import PlayerHealthBar from "@/components/molecules/game/PlayerHealthBar";
import { BasicCard } from "@/lib/cards/types/card";

type PlayerStatsProps = {
  playerCard: BasicCard;
  health: number;
  maxHealth: number;
};

export default function PlayerStats({ playerCard, health, maxHealth }: PlayerStatsProps) {

  return (
    <div className="flex flex-col items-center gap-4 min-w-64">
      <Card card={playerCard} />
      <PlayerHealthBar health={health} maxHealth={maxHealth} />
    </div>
  );
}
