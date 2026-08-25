<?php

declare(strict_types=1);

namespace App\Command;

use App\Game\AbstractCard;
use App\Game\Card\CardActions;
use App\Game\Card\CardState;
use App\Game\Card\Character\AbstractCharacterCard;
use App\Game\Card\Consumable\AbstractConsumableCard;
use App\Game\Card\EffectCollection;
use App\Game\Card\Monster\AbstractMonsterCard;
use App\Game\Card\MonsterCardState;
use App\Game\Card\Passive\AbstractPassiveCard;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('app:update:card-list')]
final class UpdateCardListCommand
{
    private const string BASE_NAMESPACE = 'App\\Game\\Card\\';

    private const array CLASSES_TO_IGNORE = [
        AbstractCard::class,
        AbstractMonsterCard::class,
        AbstractCharacterCard::class,
        AbstractPassiveCard::class,
        AbstractConsumableCard::class,
        CardState::class,
        CardActions::class,
        MonsterCardState::class,
        EffectCollection::class,
    ];

    private const array FOLDERS_TO_IGNORE = [
        'Effect',
        'Interface',
        'Trait',
    ];

    private OutputInterface $output;

    public function __invoke(OutputInterface $output, #[Option('Path to the card list file', 'filePath')] string $filePath = 'resources/cards_list.php')
    {
        $this->output = $output;

        if (!file_exists($filePath)) {
            $output->writeln(\sprintf('<info>File %s does not exist. Creating it...</info>', $filePath));
            touch($filePath);
        }

        $cards = $this->findCardsInDirectory($this->getCardFolder(), self::BASE_NAMESPACE);

        $output->writeln(\sprintf('<info>Found %u cards in the codebase.</info>', count($cards)));
        $namespace = substr(self::BASE_NAMESPACE, 0, -1);

        $content = <<<EOF
            <?php

            use {$namespace};

            return [

            EOF;
        foreach ($cards as $id => $class) {
            $formattedClass = str_replace($namespace, 'Card', $class);
            $content .= \sprintf("\t'%s' => %s::class,\n", $id, $formattedClass);
        }

        $content .= "];\n";

        file_put_contents($filePath, $content);

        $output->writeln(\sprintf('<info>New card list has been generated in %s</info>', $filePath));

        return Command::SUCCESS;
    }

    /**
     * @return array<string, class-string<AbstractCard>>
     */
    private function findCardsInDirectory(string $dir, string $baseNamespace): array
    {
        if (\in_array(basename($dir), self::FOLDERS_TO_IGNORE, true)) {
            return [];
        }

        $cards = [];

        foreach (scandir($dir) as $file) {
            if (\in_array($file, ['.', '..'], true)) {
                continue;
            }

            if (!str_ends_with($file, '.php') && is_dir($newDir = $dir.'/'.$file)) {
                $cards = array_merge($cards, $this->findCardsInDirectory($newDir, $baseNamespace.$file.'\\'));

                continue;
            }

            /** @var class-string<AbstractCard> $class */
            $class = $baseNamespace.pathinfo($file, PATHINFO_FILENAME);

            if (!class_exists($class)) {
                $this->output->writeln(\sprintf('<error>Class %s does not exist. Skipping file %s.</error>', $class, $file));
            }

            if (\in_array($class, self::CLASSES_TO_IGNORE, true)) {
                continue;
            }

            if (!is_a($class, AbstractCard::class, true)) {
                $this->output->writeln(\sprintf('<error>Class %s does not extend %s. Skipping file %s.</error>', $class, AbstractCard::class, $file));
                continue;
            }

            $card = new $class();

            $cards[$card->getId()] = $class;
        }

        return $cards;
    }

    private function getCardFolder(): string
    {
        return dirname(__DIR__).'/Game/Card';
    }
}
