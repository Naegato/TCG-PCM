import CardsHand from "../../organisms/CardsHand";
import { GameContext } from "@/contexts/GameContext";
import { useContext } from "react";
import PlayerStats from "@/components/molecules/game/PlayerStats";
import { PlayerState } from "@/lib/game/type/gameState";

type Props = {
  player: PlayerState;
  asOpponent?: boolean;
};

export default function PlayerPanel({ player, asOpponent = false }: Props) {
  const { getCardById } = useContext(GameContext);

	const playerCard = getCardById(player.characterCardId);

  return (
    <div className={`items-center gap-6 bg-green-800 p-3 rounded-lg`}>
			<div className="flex flex-row items-center gap-60">
				{playerCard && <PlayerStats playerCard={playerCard} health={player.healthPoints} maxHealth={player.maxHealthPoints} />}

				{!asOpponent && (
					<div className="relative flex gap-2 mt-4 justify-center">
					<CardsHand
						cards={player.hand
							.map((cardId: string) => getCardById(cardId))
							.filter((card): card is NonNullable<typeof card> => card !== undefined)}
					/>
					</div>
				)}
			</div>
			
			<div>
			Draw pile
			</div>
    </div>
  );
}
