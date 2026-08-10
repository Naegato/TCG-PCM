<?php

declare(strict_types=1);

namespace App\Dev;

use App\Game\Card\Character\AbstractCharacterCard;
use App\Game\Card\Consumable\AbstractConsumableCard;
use App\Game\Card\Interface\CardAwareInterface;
use App\Game\Card\Interface\TurnAwareInterface;
use App\Game\Card\Monster\AbstractMonsterCard;
use App\Game\Card\Passive\AbstractPassiveCard;
use App\Game\Card\Trait\CardAwareTrait;
use App\Game\Card\Trait\TurnAwareTrait;
use App\Tests\Unit\Game\Card\CardTestCase;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Util\ClassSource\Model\ClassData;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

final class MakeCard extends AbstractMaker
{
    private const CARD_TYPES = [
        'character' => AbstractCharacterCard::class,
        'consumable' => AbstractConsumableCard::class,
        'passive' => AbstractPassiveCard::class,
        'monster' => AbstractMonsterCard::class,
    ];

    private const string BASE_NAMESPACE = 'Game\\Card';

    public static function getCommandName(): string
    {
        return 'make:card';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a new card class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the card class (e.g. <fg=yellow>BlablaCard</>)')
            ->addArgument('type', InputArgument::OPTIONAL, 'The type of card: '.implode(', ', self::CARD_TYPES))
            ->addOption('test', 't', InputOption::VALUE_NEGATABLE, 'Whether to generate a test class for the card (default: true)', true)
            ->addOption('turnAware', 'a', InputOption::VALUE_NEGATABLE, 'Whether the card should be turn aware (default: false)', false)
            ->addOption('cardAware', 'c', InputOption::VALUE_NEGATABLE, 'Whether the card should be card aware (default: false)', false);

        $inputConfig->setArgumentAsNonInteractive('type');
    }

    public function configureDependencies(DependencyBuilder $dependencies) {}

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (!($name = $input->getArgument('name'))) {
            $name = $io->ask('Enter a card name', validator: Validator::notBlank(...));
        }

        if (null !== ($type = $input->getArgument('type'))) {
            if (null === (self::CARD_TYPES[$type] ?? null)) {
                throw new RuntimeCommandException(\sprintf(
                    'The card type must be one of "%s", "%s" given.',
                    implode('", "', array_keys(self::CARD_TYPES)),
                    (string) $type,
                ));
            }
        } else {
            $input->setArgument('type', $io->choice('Which card type would you like?', self::CARD_TYPES));
        }

        $input->setArgument('name', $name);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        /** @var string $name */
        $name = $input->getArgument('name');
        /** @var string $type */
        $type = $input->getArgument('type');

        $interfaces = array_filter([
            $input->getOption('turnAware') ? TurnAwareInterface::class : null,
            $input->getOption('cardAware') ? CardAwareInterface::class : null,
        ]);

        $traits = array_filter([
            $input->getOption('turnAware') ? TurnAwareTrait::class : null,
            $input->getOption('cardAware') ? CardAwareTrait::class : null,
        ]);

        $cardClassData = ClassData::create(
            $this->getNamespaceForType($type).'\\'.$name,
            'Card',
            self::CARD_TYPES[$type],
            false,
            array_merge($interfaces, $traits),
        );

        $generator->generateClassFromClassData($cardClassData, "templates/dev/{$type}.tpl.php", [
            'name' => $name,
            'id' => ucfirst(str_replace('Card', '', $name)),
            'interfaces' => $this->generateInterface($interfaces),
            'traits' => $this->generateTrait($traits),
        ]);

        if ($input->getOption('test')) {
            $testClassData = ClassData::create(
                \sprintf('Tests\Unit\%s\%s', $this->getNamespaceForType($type), $name),
                'CardTest',
                CardTestCase::class,
                false,
                [
                    $cardClassData->getClassName().'Card',
                ],
            );

            $generator->generateClassFromClassData($testClassData, 'templates/dev/test.tpl.php', [
                'card_name' => ucfirst($name),
            ]);
        }

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            'Next: Open your new card class and start customizing it.',
            \sprintf('Don\'t forget to update the card list (<fg=yellow>make card-list</>)'),
        ]);
    }

    private function getNamespaceForType(string $type): string
    {
        return match ($type) {
            'character' => self::BASE_NAMESPACE.'\\Character',
            'monster' => self::BASE_NAMESPACE.'\\Monster',
            'consumable' => self::BASE_NAMESPACE.'\\Consumable',
            'passive' => self::BASE_NAMESPACE.'\\Passive',
            default => self::BASE_NAMESPACE,
        };
    }

    private function generateInterface(array $interfaces): string
    {
        return [] === $interfaces ? '' : ' implements '.implode(', ', array_map(static fn(string $interface) => '\\'.$interface, $interfaces));
    }

    private function generateTrait(array $traits): string
    {
        $result = '';

        foreach ($traits as $trait) {
            $result .= "\tuse \\{$trait};\n";
        }

        return $result;
    }
}
