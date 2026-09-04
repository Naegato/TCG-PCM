<?php

use App\Enum\CardEffectEnum;
use App\Enum\CardRarityEnum;

return [
    // Cards
    'card' => [
        'Benjamin' => [
            'name' => 'Benjamin',
            'description' => 'Apply {{effect}} to {{value}} card',
        ],
        'Banana' => [
            'name' => 'Banana',
            'description' => 'Heal your character by {{value}} HP.',
        ],
        'BananaFarm' => [
            'name' => 'Banana Farm',
            'description' => 'Gain {{value}} coin at the start of each turn.',
        ],
        'Pierrot' => [
            'name' => 'Pierrot',
            'description' => 'Apply {{effect}} {{value1}} card every {{value2}} turns.',
        ],
        'Philippe' => [
            'name' => 'Philippe!',
            'description' => 'Screams so loud it applies {{effect}} to every card in the opponent\'s play area.',
        ],
        'D6' => [
            'name' => 'D6',
            'description' => 'Roll a six-sided dice and draw that many cards',
        ],
        'Stonks' => [
            'name' => 'Stonks',
            'description' => 'Gains {{value}} coins at the start of each turn. Also, at the end of each turn, gain {{value2}}% of your current coins as interest (up to {{const}} coins).',
        ],
        'Gitman' => [
            'name' => 'Gitman',
            'description' => 'Does {{value}} time per commits in this project, divided by {{divisor}}.',
        ],
        'Spicy-D6' => [
            'name' => 'Spicy D6',
            'description' => 'Roll a six-sided dice and deal {{value}} times that result as damage. Then take half of the inflicted damage.',
        ],
        'Redbloons' => [
            'name' => 'Red Bloons',
            'description' => 'A cute little balloon',
        ],
        'CamoBloon' => [
            'name' => 'Camo Bloon',
            'description' => 'Has a {{value}}% chance to dodge incoming damage.',
        ],
        'MOAB' => [
            'name' => 'MOAB',
            'description' => 'A massive bloon.',
        ],
        'LeadBloon' => [
            'name' => 'Lead Bloon',
            'description' => 'Reduces incoming damage by {{value}}.',
        ],
        'Zeppelin' => [
            'name' => 'Zeppelin',
            'description' => 'When played, all other bloons gain +{{value}} attack and HP.',
        ],
        'AlchemistMonkey' => [
            'name' => 'Alchemist Monkey',
            'description' => 'At the end of each turn, randomly gives +{{value}} attack to another allied monster.',
        ],
        'MechaPainter' => [
            'name' => 'Mecha Painter',
            'description' => 'Reduces incoming damage by {{value}}. At the end of each turn, randomly deals {{value2}} damage to an allied monster.',
        ],
        'DartMonkey' => [
            'name' => 'Dart Monkey',
            'description' => 'A cute monkey.',
        ],
        'BoomerangMonkey' => [
            'name' => 'Boomerang Monkey',
            'description' => 'Loses {{value}} HP after attacking.',
        ],
        'SuperMonkey' => [
            'name' => 'Super Monkey',
            'description' => 'Gains +{{value}} attack and HP for each  monkey monster on your side when played.',
        ],
        'NinjaMonkey' => [
            'name' => 'Ninja Monkey',
            'description' => 'Dodges the first attack it takes after being played.',
        ],
        'SniperMonkey' => [
            'name' => 'Sniper Monkey',
            'description' => 'A high-precision monkey with a deadly shot.',
        ],
        'WizardMonkey' => [
            'name' => 'Wizard Monkey',
            'description' => 'When played, deals {{value}} damage to all opposing monsters.',
        ],
        'MonkeyVillage' => [
            'name' => 'Monkey Village',
            'description' => 'At the start of each of your turns, gives +{{value}} attack to every allied monkey.',
        ],
        'BlackBloon' => [
            'name' => 'Black Bloon',
            'description' => 'Reduces incoming damage by {{value}}.',
        ],
        'CeramicBloon' => [
            'name' => 'Ceramic Bloon',
            'description' => 'A tough bloon with a thick ceramic shell.',
        ],
        'DDT' => [
            'name' => 'DDT',
            'description' => 'Has a {{value}}% chance to dodge incoming damage, otherwise reduces it by {{value2}}.',
        ],
        'BFB' => [
            'name' => 'BFB',
            'description' => 'A brutal floating behemoth.',
        ],
        'HackedZone' => [
            'name' => 'Hacked Zone',
            'description' => 'Apply {{effect}} to all cards in both side',
        ],
        'PierreSaidNoMonsterZone' => [
            'name' => 'Pierre Said "No Monster Zone"',
            'description' => 'Discard all active monsters',
        ],
        'Bomb' => [
            'name' => 'Bomb',
            'description' => 'Deal {{value}} damage to all monsters in play.',
        ],
        'Dart' => [
            'name' => 'Dart',
            'description' => 'Deal {{value}} damage to a targeted card.',
        ],
        'Fortnite' => [
            'name' => 'Fortnite',
            'description' => 'Pick a random monster in play (if there is only one, it is picked automatically), discard all other cards in play, then give it Victory royale (+{{health}} HP and +{{attack}} attack).',
        ],
        'Pills' => [
            'name' => 'Pills!',
            'description' => 'Random effect!',
        ],
        'Horsepill' => [
            'name' => 'Horsepill!',
                'description' => 'Very random effect!!!',
        ],
        'Placenta' => [
            'name' => 'Placenta',
            'description' => 'Heal {{value}} HP at the start of each turn.',
        ],
        'ImStillStanding' => [
            'name' => "I'm Still Standing",
            'description' => 'When the player dies, he comes back to life with {{value}}% of his max HP.',
        ],
        'Justice' => [
            'name' => 'Justice',
            'description' => 'Make current player draw has many cards equal to the number of cards in other player hand.',
        ],
        'Clotty' => [
            'name' => 'Clotty',
            'description' => 'Dangerous small critter.',
        ],
        'GrilledClotty' => [
            'name' => 'Grilled Clotty',
            'description' => 'Loses 1 HP at the end of every turn.',
        ],
        'Henry' => [
            'name' => 'Henry',
            'description' => 'Discarded at the start of its owner\'s next turn.',
        ],
        'HoneyBee' => [
            'name' => 'Honey Bee',
            'description' => 'When it attacks, heal your character by {{value}} HP.',
        ],
        'RadicalRat' => [
            'name' => 'Radical Rat',
            'description' => 'When it is played or dies, it deals {{value}} damage to every opposing monster and the opposing character at the same time.',
        ],
        'ConsolationPrice' => [
            'name' => 'Consolation Price',
            'description' => 'Each monster death grans {{value}} gold to the player.',
        ],
        'consolation_prices' => [
            'name' => 'Consolation Price',
            'description' => 'Each monster death grans {{value}} gold to the player.',
        ],
        'ViciousBee' => [
            'name' => 'Vicious Bee',
            'description' => 'When kill, give a <card>ViciousStinger</card>',
        ],
        'ViciousStinger' => [
            'name' => 'Vicious Stinger',
            'description' => 'Give <effect>DMGBoost</effect> to one monster',
        ],
        'Play2Hurt' => [
            'name' => 'Play 2 Hurt',
            'description' => 'When you play a card, deal {{value}} damage to the card owner.',
        ],
        'StackyStackito' => [
            'name' => 'Stacky Stackito',
            'description' => 'Every {{value}} turns, deal coins count as damage.',
        ],
        'RiskyBet' => [
            'name' => 'Risky Bet',
            'description' => 'Roll a 10-sided dice. If you roll a 9 or a 10, deal {{value}} damage to the opponent. Otherwise, deal {{value2}} damage to yourself.',
        ],
        'Necromancian' => [
            'name' => 'Prince of Darkness',
            'description' => 'Redrawn a card, at the end of turn.',
        ],
        'Isaac' => [
            'name' => 'Isaac',
            'description' => 'Deal 5 damage to a random opponent card at the start of each turn.',
        ],
        'Communism' => [
            'name' => 'Communism',
            'description' => 'Split your coins evenly with your opponent.',
        ],
        'BloodSucker' => [
            'name' => 'Blood sucker',
            'description' => 'Does {{value}} to all players on turn start',
        ],
        'TheHand' => [
            'name' => 'The hand',
            'description' => 'Discard a random card from your opponent play area.',
        ],
        'TheLost' => [
            'name' => 'The Lost',
            'description' => 'Dodges the first attack it takes after being played.',
        ],
        'Coins' => [
            'name' => 'Coins',
            'description' => 'Gains {{value}} coins.',
        ],
        'Chaos' => [
            'name' => 'Chaos',
            'description' => 'Replaces every card in play with random other cards.',
        ],
        'Crypto4Noob' => [
            'name' => 'Crypto4Noob',
            'description' => 'Does as much damage as the current price of Bitcoin in euros, divided by 3000.',
        ],
        'Charlie' => [
            'name' => 'Charlie',
            'description' => 'Uses his masonry skills to craft a random passive at the start of the player\'s turn.',
        ],
        'Maxime' => [
            'name' => 'Maxime',
            'description' => 'Eats {{value1}} random opponent card every {{value2}} turns (characters take {{value3}} damage instead).',
        ],
        'MimeticPrismRuby' => [
            'name' => 'Mimetic Prism Ruby',
            'description' => 'Copy a random monster card in play. Deals 2x damage and has 1.5x HP.',
        ],
        'MimeticPrismAmethyst' => [
            'name' => 'Mimetic Prism Amethyst',
            'description' => 'Copy a random monster card in play. Deals 4x damage and has 1 HP.',
        ],
        'MimeticPrismSaphir' => [
            'name' => 'Mimetic Prism Saphir',
            'description' => 'Copy a random monster card in play. Deals 0.5x damage and has 2x HP.',
        ],
        'Wololo' => [
            'name' => 'Wololo',
            'description' => 'Convert an enemy monster or passive, making it change sides.',
        ],
        'Siren' => [
            'name' => 'Siren',
            'description' => 'Every {{value}} turns, makes a random enemy monster change sides.',
        ],
        'MegaClotty' => [
            'name' => 'Mega Clotty',
            'description' => 'When played, absorbs all clotties in play, gaining +{{value}} HP and +{{value2}} attack for each clottie absorbed. When it dies, spawns two clotties.',
        ],
        'DecoyBomb' => [
            'name' => 'Decoy Bomb',
            'description' => 'A fake bomb. Steals {{value}} coins from the opponent instead of exploding.',
        ],
        'SuperMonkeyBomb' => [
            'name' => 'Super Monkey Bomb',
            'description' => 'Deal {{value}} damage to all monsters in play.',
        ],
        'TimeBomb' => [
            'name' => 'Time Bomb',
            'description' => 'After {{value2}} turns, deals {{value}} damage to the opponent, then is discarded.',
        ],
        'MegaBomb' => [
            'name' => 'Mega Bomb',
            'description' => 'Every {{value2}} turns, deals {{value}} damage to the opponent.',
        ],
        'BombFactory' => [
            'name' => 'Bomb Factory',
            'description' => 'Every {{value}} turn(s), generates a {{card}}.',
        ],
        'ChainBomb' => [
            'name' => 'Chain Bomb',
            'description' => 'Deal {{value}} damage to the opponent. Also detonates a random other bomb card in play, if any.',
        ],
        'TrollBomb' => [
            'name' => 'Troll Bomb',
            'description' => 'Usually deals only {{value}} damage... but there is a small chance it detonates every bomb in play instead.',
        ],
        'StickyBomb' => [
            'name' => 'Sticky Bomb',
            'description' => 'Deal {{value}} damage to a targeted card.',
        ],
    ],
    // Effects
    'effects' => [
        CardEffectEnum::HACKED->value => [
            'name' => 'Hacked',
        ],
        CardEffectEnum::TORNED->value => [
            'name' => 'Torned',
        ],
        CardEffectEnum::POWER_BOOST->value => [
            'name' => 'Damage Boost',
        ],
    ],
    // Rarities
    'rarity' => array_reduce(CardRarityEnum::cases(), function ($carry, $item) {
        $carry[$item->value] = [
            'name' => $item->name,
        ];

        return $carry;
    }, []),
];
