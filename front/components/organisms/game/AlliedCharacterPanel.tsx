import { GameContext } from "@/contexts/GameContext";
import { useContext } from "react";
import { PlayerState } from "@/lib/game/type/gameState";
import PlayerHealthBar from "@/components/molecules/game/PlayerHealthBar"; // Import PlayerHealthBar
import Card from "@/components/molecules/Card";

type Props = {
  player: PlayerState;
  className?: string;
};

export default function AlliedCharacterPanel({ player, className }: Props) {
  const { getCardById } = useContext(GameContext);

  const playerCard = getCardById(player.characterCardId);

  return (
    <>
      <div
        className={`grid grid-cols-[1fr_auto_1fr] items-center w-full p-4 ${className}`}
      >
        {playerCard && (
          <div className="col-start-1 justify-self-start relative">
            <div className="absolute -top-30 left-1/2 -translate-x-1/2">
              <PlayerHealthBar
                health={player.healthPoints}
                maxHealth={player.maxHealthPoints}
              />{" "}
            </div>
            <Card card={playerCard} />
          </div>
        )}
      </div>
    </>
  );
}
