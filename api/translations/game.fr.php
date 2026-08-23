<?php

use App\Enum\CardEffectEnum;
use App\Enum\CardRarityEnum;

return [
    // Cards
    'card' => [
        'Benjamin' => [
            'name' => 'Benjamin',
            'description' => 'Applique {{effect}} à {{value}} carte',
        ],
        'Banana' => [
            'name' => 'Banane',
            'description' => 'Soigne votre personnage de {{value}} PV.',
        ],
        'BananaFarm' => [
            'name' => 'Ferme à bananes',
            'description' => 'Gagne {{value}} pièce(s) au debut de chaque tour.',
        ],
        'Pierrot' => [
            'name' => 'Pierrot',
            'description' => 'Applique {{effect}} à {{value1}} carte tous les {{value2}} tours.',
        ],
        'Maxime' => [
            'name' => 'Maxime',
            'description' => 'Mange {{value1}} carte adverse aléatoire tous les {{value2}} tours (les personnages prennent {{value3}} dégâts à la place).',
        ],
        'Charlie' => [
            'name' => 'Charlie',
            'description' => 'Utilise ses talents de maçon afin de bricoler {{value1}} passif aléatoire tous les {{value2}} tours.',
        ],
        'Stonks' => [
            'name' => 'Stonks',
            'description' => 'Gagne {{value}} pièces au début de chaque tour. De plus, à la fin de chaque tour, gagne {{value2}}% de vos pièces actuelles en intérêts (jusqu\'à {{const}} pièces).',
        ],
        'D6' => [
            'name' => 'D6',
            'description' => 'Lancez un dé à six faces et piochez autant de cartes.',
        ],
        'Gitman' => [
            'name' => 'Gitman',
            'description' => 'Inflige {{value}} fois le nombre de commits dans ce projet, divisé par {{divisor}}.',
        ],
        'Spicy-D6' => [
            'name' => 'D6 Épicé',
            'description' => 'Lancez un dé à six faces et infligez {{value}} fois le résultat en dégâts. Puis subissez la moitié des dégâts infligés.',
        ],
        'Redbloons' => [
            'name' => 'Ballon Rouge',
            'description' => 'Petit ballon tout mignon.',
        ],
        'CamoBloon' => [
            'name' => 'Ballon Camo',
            'description' => 'A {{value}}% de chances d\'esquiver les dégâts reçus.',
        ],
        'MOAB' => [
            'name' => 'MOAB',
            'description' => 'Un énorme ballon.',
        ],
        'LeadBloon' => [
            'name' => 'Ballon de Plomb',
            'description' => 'Réduit les dégâts reçus de {{value}}.',
        ],
        'Zeppelin' => [
            'name' => 'Zeppelin',
            'description' => 'Quand il est joué, tous les autres bloons gagnent +{{value}} attaque et PV.',
        ],
        'AlchemistMonkey' => [
            'name' => 'Singe Alchimiste',
            'description' => 'À la fin de chaque tour, donne aléatoirement +{{value}} attaque à un autre monstre allié.',
        ],
        'MechaPainter' => [
            'name' => 'Mecha-Peintre',
            'description' => 'Réduit les dégâts reçus de {{value}}. À chaque fin de tour (tours adversaires compris), inflige {{value2}} dégâts à un monstre allié aléatoire.',
        ],
        'DartMonkey' => [
            'name' => 'Singe',
            'description' => 'Un potit singe.',
        ],
        'BoomerangMonkey' => [
            'name' => 'Singe Boomerang',
            'description' => 'Perd {{value}} PV après avoir attaqué.',
        ],
        'SuperMonkey' => [
            'name' => 'Super singe',
            'description' => 'Gagne +{{value}} attaque et PV pour chaque monstre singe de votre côté lors de son arrivée en jeu.',
        ],
        'NinjaMonkey' => [
            'name' => 'Singe Ninja',
            'description' => 'Esquive la première attaque qu\'il subit après avoir été joué.',
        ],
        'SniperMonkey' => [
            'name' => 'Singe Sniper',
            'description' => 'Un singe de précision au tir mortel.',
        ],
        'WizardMonkey' => [
            'name' => 'Singe Sorcier',
            'description' => 'Quand il est joué, inflige {{value}} dégâts à tous les monstres adverses.',
        ],
        'MonkeyVillage' => [
            'name' => 'Village de Singes',
            'description' => 'Au début de chacun de vos tours, donne +{{value}} attaque à tous les singes alliés.',
        ],
        'BlackBloon' => [
            'name' => 'Ballon Noir',
            'description' => 'Réduit les dégâts reçus de {{value}}.',
        ],
        'CeramicBloon' => [
            'name' => 'Ballon en Céramique',
            'description' => 'Un ballon résistant à la coque épaisse.',
        ],
        'DDT' => [
            'name' => 'DDT',
            'description' => 'A {{value}}% de chances d\'esquiver les dégâts reçus, sinon les réduit de {{value2}}.',
        ],
        'BFB' => [
            'name' => 'BFB',
            'description' => 'Un colosse volant brutal.',
        ],
        'HackedZone' => [
            'name' => 'Zone Piratée',
            'description' => 'Applique {{effect}} à toutes les cartes des deux côtés.',
        ],
        'PierreSaidNoMonsterZone' => [
            'name' => 'Pierre a dit "Pas de Zone Monstre"',
            'description' => 'Défaussez tous les monstres actifs.',
        ],
        'Bomb' => [
            'name' => 'Bombe',
            'description' => 'Inflige {{value}} dégâts à tous les monstres en jeu.',
        ],
        'Dart' => [
            'name' => 'Fléchette',
            'description' => 'Inflige {{value}} dégâts à une carte ciblée.',
        ],
        'Fortnite' => [
            'name' => 'Fortnite',
            'description' => 'Choisit un monstre en jeu aléatoirement, défausse toutes les autres cartes en jeu. Ce monstre fait top 1 et gagne +{{health}} PV et +{{attack}} attaque.',
        ],
        'Pills' => [
            'name' => 'Pillules !',
            'description' => 'Effet aléatoire !',
        ],
        'Horsepill' => [
            'name' => 'Pillule de cheval !!!',
            'description' => 'Effet très aléatoire !!!',
        ],
        'Placenta' => [
            'name' => 'Placenta',
            'description' => 'Soigne {{value}} PV au début de chaque tour.',
        ],
        'ImStillStanding' => [
            'name' => "I'm Still Standing",
            'description' => 'Lorsque le joueur meurt, il revient à la vie avec {{value}}% de ses PV max.',
        ],
        'Justice' => [
            'name' => 'Justice',
            'description' => 'Faites piocher au joueur actuel autant de cartes que le nombre de cartes dans la main de l\'autre joueur.',
        ],
        'Clotty' => [
            'name' => 'Coagulé',
            'description' => 'Un potit monstre.',
    ],
        'GrilledClotty' => [
            'name' => 'Coagulé grillé',
            'description' => 'Perd 1 PV à la fin de chaque tour.',
        ],
        'Henry' => [
            'name' => 'Henry',
            'description' => 'Est défaussé au début du prochain tour de son propriétaire.',
        ],
        'HoneyBee' => [
            'name' => 'Abeille à miel',
            'description' => 'Quand elle attaque, soigne votre personnage de {{value}} PV.',
        ],
        'RadicalRat' => [
            'name' => 'Rat Radical',
            'description' => 'Lorsqu\'il est joué ou qu\'il meurt, inflige {{value}} dégâts à tous les monstres et personnages adverses.',
        ],
        'ConsolationPrice' => [
            'name' => 'Prix de consolation',
            'description' => 'Chaque mort de monstre accorde {{value}} pièces au joueur.',
        ],
        'consolation_prices' => [
            'name' => 'Prix de consolation',
            'description' => 'Chaque mort de monstre accorde {{value}} pièces au joueur.',
        ],
        'ViciousBee' => [
            'name' => 'Abeille Vicieuse',
            'description' => 'A sa mort, donne une <card>ViciousStinger</card>.',
        ],
        'ViciousStinger' => [
            'name' => 'Dard Vicieux',
            'description' => 'Applique un buff de dégâts à une carte.',
        ],
        'Play2Hurt' => [
            'name' => 'Jouer pour blesser',
            'description' => 'Inflige {{value}} dégâts a un joeueur à chaque carte jouée.',
        ],
        'StackyStackito' => [
            'name' => 'Stacky Stackito',
            'description' => 'Chaque {{value}} tour, fait le nombre de pièces comme dégats.',
        ],
        'RiskyBet' => [
            'name' => 'Pari risqué',
            'description' => 'Lancez un dé à dix faces. Si le résultat est de 9 ou 10, infligez {{value}} dégâts à votre adversaire. Sinon, infligez {{value2}} dégâts à vous-même.',
        ],
        'Necromancian' => [
            'name' => 'Singe Nécromancien',
            'description' => 'Ressuscite une carte aléatoire à la fin du tour.',
        ],
        'Isaac' => [
            'name' => 'Isaac',
            'description' => 'Inflige 5 dégâts à une carte adverse aléatoire au début du tour.',
        ],
        'Communism' => [
            'name' => 'Communisme',
            'description' => 'Partage equitablement les pièces entre les joueurs.',
        ],
        'BloodSucker' => [
            'name' => 'Suceur de sang',
            'description' => 'Inflige {{value}} à tous les joueurs à chaque début de tour',
        ],
        'TheHand' => [
            'name' => 'La main',
            'description' => 'Défausse une carte aléatoire de la zone de jeu de votre adversaire.',
        ],
        'TheLost' => [
            'name' => 'Le Perdant',
            'description' => 'Esquive la première attaque qu\'il subit après avoir été joué.',
        ],
        'Coins' => [
            'name' => 'Pièces',
            'description' => 'Gagne {{value}} pièces.',
        ],
        'Chaos' => [
            'name' => 'Chaos',
            'description' => 'Remplace toutes les cartes en jeu par d\'autres cartes aléatoires.',
        ],
        'Crypto4Noob' => [
            'name' => 'Crypto4Noob',
            'description' => 'Inflige des dégâts égaux au prix actuel du Bitcoin en euros, divisé par 3000.',
        ],
        'MimeticPrismRuby' => [
            'name' => 'Prisme Mimétique Rubis',
            'description' => 'Copie une carte monstre aléatoire en jeu. Inflige 2x dégâts et a 0.5x PV.',
        ],
        'MimeticPrismAmethyst' => [
            'name' => 'Prisme Mimétique Améthyste',
            'description' => 'Copie une carte monstre aléatoire en jeu. Inflige 4x dégâts et a 1 PV.',
        ],
        'MimeticPrismSaphir' => [
            'name' => 'Prisme Mimétique Saphir',
            'description' => 'Copie une carte monstre aléatoire en jeu. Inflige 0.5x dégâts et a 2x PV.',
        ],
        'Wololo' => [
            'name' => 'Wololo',
            'description' => 'Convertie un monstre ou passif adverse, le faisant changer de camp.',
        ],
        'Siren' => [
            'name' => 'Sirène',
            'description' => 'Tous les {{value}} tours, fait changer de camp un monstre adverse aléatoire.',
        ],
        'MegaClotty' => [
            'name' => 'Méga Coagulé',
            'description' => 'Quand il est joué, absorbe tous les coagulés en jeu, gagnant +{{value}} PVs et +{{value2}} attaque pour chaque coagulé absorbé. Quand il meurt, fait apparaître {{value3}} <card>Clotty</card>.',
        ],
        'Racism' => [
            'name' => 'Racisme',
            'description' => 'Inflige {{value}} dégâts à tous les monstres qui ne sont pas de <set>original</set>.',
        ],
        'Goofy' => [
            'name' => 'Goofy',
            'description' => 'Les stats attaques/pv varient de {{value}} à {{value2}} aléatoirement à chaque tour.',
        ],
        'ScratchGameAddict' => [
            'name' => 'Accro au jeu de grattage',
            'description' => 'Inflige {{value}} dégâts en fonction du cours des actions <const>FDJU</const>.',
        ],
        'DeadGameXD' => [
            'name' => 'DeadGameXD',
            'description' => 'Les pvs équivaut aux nombre de joueurs / 10, et les dégats le score Metacritic / 10 jeux: <const>paladins</const>.',
        ],
        'BombFest' => [
            'name' => 'Festival de Bombes',
            'description' => 'Pose toutes les bombes sur le terrain, vous gagnez {{value}} dégâts pour chaque bombe posée.',
        ],
        'DeadCat' => [
            'name' => 'Chat mort',
            'description' => 'Quand le joueur meurt, il revient à la vie avec {{value}} PV. Peut être utilisé {{value2}} fois avant d\'être défaussé.',
        ],
        'DataMiner' => [
            'name' => 'Data Miner',
            'description' => 'Gagne {{value}} pièces au début de chaque tour. Chaque tour, le gain augmente de {{const}} pièces supplémentaires.',
        ],
        'DataCenter' => [
            'name' => 'DataCenter',
            'description' => 'Fait apparaître {{value}} {{card}} tous les {{value2}} tours.',
        ],
    ],
    // Effects
    'effects' => [
        CardEffectEnum::HACKED->value => [
            'name' => 'Hacké',
            'description' => "Change les valeurs d'une carte",
        ],
        CardEffectEnum::TORNED->value => [
            'name' => 'Tordu',
            'description' => "La carte ne s'active pas à chaque fois",
        ],
        CardEffectEnum::POWER_BOOST->value => [
            'name' => 'Boost de puissance',
            'description' => "Augmente les dégâts d'une carte",
        ],
    ],
    'rarity' => [
        CardRarityEnum::COMMON->value => 'Commune',
        CardRarityEnum::UNCOMMON->value => 'Non commune',
        CardRarityEnum::RARE->value => 'Rare',
        CardRarityEnum::EPIC->value => 'Epique',
        CardRarityEnum::LEGENDARY->value => 'Légendaire',
    ],
];
