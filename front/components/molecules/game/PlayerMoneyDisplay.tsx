import Image from "@/components/atoms/Image";

type PlayerMoneyDisplayProps = {
  money: number;
};

export default function PlayerMoneyDisplay({ money }: PlayerMoneyDisplayProps) {
  return (
    <div className="flex flex-row items-center gap-2 flex-nowrap bg-amber-50 rounded-full p-2">
      <Image src="/icons/coins.svg" alt="Money" width={32} height={32} />
      <span className="text-amber-500 text-xl">{money}</span>
    </div>
  );
}
