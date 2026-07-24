<?php
/**
 * Génère database/seed_bulk.sql avec >100 quiz et >1000 questions par catégorie/sous-catégorie.
 *
 * Usage:
 *   php bin/generate_bulk_seed.php
 *   php bin/generate_bulk_seed.php --verify   # affiche les comptes attendus sans écrire le fichier
 *
 * Paramètres (constants ci-dessous) :
 *   - 100 nouveaux quiz par catégorie (en plus des quiz existants dans seed.sql)
 *   - 10 questions par quiz généré (= 1000 questions supplémentaires par catégorie)
 */

declare(strict_types=1);

const QUIZZES_PER_CATEGORY = 100;
const QUESTIONS_PER_QUIZ   = 10;
const QUIZ_ID_START        = 1000;
const QUESTION_ID_START    = 10000;
const BATCH_SIZE           = 200;

$baseDir    = dirname(__DIR__);
$outputPath = $baseDir . '/database/seed_bulk.sql';
$verifyOnly = in_array('--verify', $argv ?? [], true);

/** @var array<int, array{name: string, themes: string[], facts: string[][]}> */
$categories = [
    1  => ['name' => 'Astronomie', 'themes' => ['Système Solaire', 'Étoiles', 'Galaxies', 'Exoplanètes', 'Trous noirs'], 'facts' => [
        ['Mercure', 'planète la plus proche du Soleil'],
        ['Vénus', 'planète la plus chaude du Système Solaire'],
        ['Mars', 'planète dite rouge'],
        ['Jupiter', 'plus grande planète du Système Solaire'],
        ['Saturne', 'planète aux anneaux spectaculaires'],
    ]],
    2  => ['name' => 'Géographie', 'themes' => ['Capitales', 'Continents', 'Océans', 'Reliefs', 'Climats'], 'facts' => [
        ['Paris', 'capitale de la France'],
        ['Tokyo', 'capitale du Japon'],
        ['Le Nil', 'fleuve historique d\'Afrique'],
        ['L\'Everest', 'plus haut sommet du monde'],
        ['Le Pacifique', 'plus vaste océan de la Terre'],
    ]],
    3  => ['name' => 'Mathématiques', 'themes' => ['Algèbre', 'Géométrie', 'Arithmétique', 'Probabilités', 'Suites'], 'facts' => [
        ['Pythagore', 'théorème des triangles rectangles'],
        ['Pi (π)', 'rapport circonférence/diamètre d\'un cercle'],
        ['Euclide', 'Éléments de géométrie'],
        ['Fibonacci', 'suite célèbre 1, 1, 2, 3, 5…'],
        ['Gauss', 'surnommé le prince des mathématiciens'],
    ]],
    4  => ['name' => 'Informatique', 'themes' => ['Programmation', 'Réseaux', 'Web', 'Sécurité', 'Histoire du numérique'], 'facts' => [
        ['Ada Lovelace', 'pionnière de la programmation'],
        ['Tim Berners-Lee', 'inventeur du World Wide Web'],
        ['Linux', 'noyau open source de Linus Torvalds'],
        ['JavaScript', 'langage des navigateurs web'],
        ['HTTP', 'protocole de transfert hypertexte'],
    ]],
    5  => ['name' => 'Histoire', 'themes' => ['Antiquité', 'Moyen Âge', 'Révolutions', 'Empires', 'Guerres mondiales'], 'facts' => [
        ['476', 'chute de l\'Empire romain d\'Occident'],
        ['1789', 'Révolution française'],
        ['1914', 'début de la Première Guerre mondiale'],
        ['1945', 'fin de la Seconde Guerre mondiale'],
        ['Khéops', 'pharaon de la grande pyramide de Gizeh'],
    ]],
    6  => ['name' => 'Sciences & Nature', 'themes' => ['Biologie', 'Chimie', 'Physique', 'Écologie', 'Anatomie'], 'facts' => [
        ['ADN', 'molécule porteuse de l\'information génétique'],
        ['Photosynthèse', 'production d\'oxygène par les plantes'],
        ['Newton', 'lois du mouvement et gravitation'],
        ['Cellule', 'unité de base du vivant'],
        ['H2O', 'formule chimique de l\'eau'],
    ]],
    7  => ['name' => 'Littérature', 'themes' => ['Romans', 'Poésie', 'Théâtre', 'Auteurs français', 'Classiques mondiaux'], 'facts' => [
        ['Victor Hugo', 'auteur des Misérables'],
        ['Molière', 'auteur du Tartuffe'],
        ['Shakespeare', 'auteur d\'Hamlet'],
        ['Cervantes', 'auteur de Don Quichotte'],
        ['Albert Camus', 'auteur de L\'Étranger'],
    ]],
    8  => ['name' => 'Cinéma', 'themes' => ['Réalisateurs', 'Oscars', 'Genres', 'Animation', 'Cinéma français'], 'facts' => [
        ['Alfred Hitchcock', 'maître du suspense'],
        ['Steven Spielberg', 'réalisateur de E.T.'],
        ['1937', 'sortie de Blanche-Neige et les Sept Nains'],
        ['Cannes', 'festival de cinéma en France'],
        ['Le Parrain', 'film de Francis Ford Coppola'],
    ]],
    9  => ['name' => 'Art & Peinture', 'themes' => ['Renaissance', 'Impressionnisme', 'Sculpture', 'Musées', 'Courants modernes'], 'facts' => [
        ['Léonard de Vinci', 'peintre de La Joconde'],
        ['Claude Monet', 'peintre des Nymphéas'],
        ['Vincent van Gogh', 'peintre de La Nuit étoilée'],
        ['Michel-Ange', 'artiste de la chapelle Sixtine'],
        ['Pablo Picasso', 'figure majeure du cubisme'],
    ]],
    10 => ['name' => 'Mythologie', 'themes' => ['Mythologie grecque', 'Mythologie nordique', 'Mythologie égyptienne', 'Héros', 'Dieux'], 'facts' => [
        ['Zeus', 'roi des dieux grecs'],
        ['Thor', 'dieu nordique du tonnerre'],
        ['Osiris', 'dieu égyptien des morts'],
        ['Héraclès', 'héros des douze travaux'],
        ['Aphrodite', 'déesse grecque de l\'amour'],
    ]],
    11 => ['name' => 'Politique', 'themes' => ['Démocratie', 'Institutions', 'Géopolitique', 'Droit', 'Histoire politique'], 'facts' => [
        ['Montesquieu', 'théoricien de la séparation des pouvoirs'],
        ['ONU', 'Organisation des Nations Unies fondée en 1945'],
        ['Suffrage universel', 'droit de vote pour tous les citoyens adultes'],
        ['Parlement', 'assemblée législative dans un régime parlementaire'],
        ['Constitution', 'texte fondamental d\'un État'],
    ]],
    12 => ['name' => 'Socialisme', 'themes' => ['Théorie', 'Mouvements ouvriers', 'Coopératives', 'Réformisme', 'Penseurs'], 'facts' => [
        ['Karl Marx', 'auteur du Capital'],
        ['Jean Jaurès', 'figure du socialisme français'],
        ['Première Internationale', 'association ouvrière de 1864'],
        ['Robert Owen', 'pionnier du mouvement coopératif'],
        ['État-providence', 'protection sociale par l\'État'],
    ]],
    13 => ['name' => 'Anarchisme', 'themes' => ['Libertaire', 'Syndicalisme', 'Autogestion', 'Penseurs', 'Mouvements'], 'facts' => [
        ['Pierre-Joseph Proudhon', 'auteur de « La propriété, c\'est le vol ! »'],
        ['Pierre Kropotkine', 'auteur de L\'Entraide'],
        ['Emma Goldman', 'militante anarchiste américaine'],
        ['CNT', 'syndicat anarcho-syndicaliste espagnol'],
        ['Max Stirner', 'auteur de L\'Unique et sa propriété'],
    ]],
    14 => ['name' => 'Communisme', 'themes' => ['Marxisme', 'Révolutions', 'Théorie', 'XXe siècle', 'Mouvements'], 'facts' => [
        ['Manifeste du parti communiste', 'ouvrage de Marx et Engels en 1848'],
        ['Révolution d\'Octobre 1917', 'prise de pouvoir des bolcheviks en Russie'],
        ['Mao Zedong', 'dirigeant de la République populaire de Chine'],
        ['Plan quinquennal', 'planification économique soviétique'],
        ['Commune de Paris 1871', 'expérience révolutionnaire parisienne'],
    ]],
    15 => ['name' => 'Générale (Politique)', 'themes' => ['Régimes', 'Élections', 'Droit public', 'Relations internationales', 'Citoyenneté'], 'facts' => [
        ['Thomas Hobbes', 'auteur du Léviathan'],
        ['Max Weber', 'monopole de la violence légitime'],
        ['Fédéralisme', 'partage de souveraineté entre niveaux de gouvernement'],
        ['Totalitarisme', 'contrôle étendu de la société par l\'État'],
        ['Démocratie représentative', 'mandat confié à des élus'],
    ]],
    16 => ['name' => 'Musique', 'themes' => ['Classique', 'Jazz', 'Rock', 'Instruments', 'Compositeurs'], 'facts' => [
        ['Ludwig van Beethoven', 'compositeur de la Neuvième Symphonie'],
        ['Wolfgang Amadeus Mozart', 'enfant prodige autrichien'],
        ['Pink Floyd', 'groupe de rock progressif'],
        ['Trompette', 'instrument à vent en cuivre'],
        ['Reggae', 'genre musical né en Jamaïque'],
    ]],
    17 => ['name' => 'Sport', 'themes' => ['Jeux Olympiques', 'Football', 'Tennis', 'Athlétisme', 'Records'], 'facts' => [
        ['776 av. J.-C.', 'premiers Jeux olympiques antiques en Grèce'],
        ['11 joueurs', 'effectif d\'une équipe de football sur le terrain'],
        ['Usain Bolt', 'recordman du 100 mètres'],
        ['Roland-Garros', 'tournoi sur terre battue à Paris'],
        ['Judo', 'sport avec ippon et waza-ari'],
    ]],
    18 => ['name' => 'Jeux Vidéo & Pop Culture', 'themes' => ['Consoles', 'Nintendo', 'RPG', 'Esport', 'Histoire du jeu vidéo'], 'facts' => [
        ['Mario', 'mascotte emblématique de Nintendo'],
        ['The Legend of Zelda', 'saga d\'aventure avec Link'],
        ['Minecraft', 'jeu de construction le plus vendu'],
        ['Tetris', 'puzzle créé par Alexey Pajitnov en 1984'],
        ['Game Boy', 'console portable sortie en 1989'],
    ]],
    19 => ['name' => 'Gastronomie', 'themes' => ['Cuisines du monde', 'Fromages', 'Épices', 'Pâtisserie', 'Traditions'], 'facts' => [
        ['Paella', 'plat espagnol au riz et au safran'],
        ['Roquefort', 'fromage AOP au lait de brebis'],
        ['Basilic', 'herbe principale du pesto genovese'],
        ['Truffe noire', 'champignon surnommé diamant noir'],
        ['Safran', 'épice la plus chère au kilo'],
    ]],
    20 => ['name' => 'Séries TV & Animation', 'themes' => ['Séries dramatiques', 'Comédies', 'Anime', 'Streaming', 'Personnages cultes'], 'facts' => [
        ['Les Simpson', 'famille de Springfield'],
        ['Game of Thrones', 'saga du Trône de Fer'],
        ['Pokémon', 'Pikachu compagnon de Sacha'],
        ['Friends', 'six amis à New York'],
        ['Breaking Bad', 'Walter White alias Heisenberg'],
    ]],
    21 => ['name' => 'Écologie & Environnement', 'themes' => ['Climat', 'Biodiversité', 'Énergies renouvelables', 'Pollution', 'Forêts'], 'facts' => [
        ['CO2', 'gaz à effet de serre majeur'],
        ['Amazonie', 'immense forêt tropicale d\'Amérique du Sud'],
        ['Énergie solaire', 'électricité produite à partir du soleil'],
        ['Photosynthèse', 'absorption de CO2 par les plantes'],
        ['Récifs coralliens', 'refuges d\'une grande biodiversité marine'],
    ]],
];

function sqlEscape(string $value): string
{
    return str_replace(["\\", "'"], ["\\\\", "\\'"], $value);
}

function pick(array $items, int $index): mixed
{
    return $items[$index % count($items)];
}

/**
 * @return array{type: string, text: string, explanation: string, answers: array<int, array{text: string, correct: bool}>}
 */
function buildQuestion(int $catId, array $category, int $quizIndex, int $questionIndex): array
{
    $name   = $category['name'];
    $theme  = pick($category['themes'], $quizIndex + $questionIndex);
    $fact   = pick($category['facts'], $quizIndex * 3 + $questionIndex);
    $term   = $fact[0];
    $detail = $fact[1];
    $seed   = ($catId * 10000) + ($quizIndex * 10) + $questionIndex;

    if ($questionIndex % 5 === 4) {
        $isTrue = ($seed % 2) === 0;
        return [
            'type'        => 'true_false',
            'text'        => "Vrai ou faux : en {$name}, « {$term} » est associé à « {$detail} ».",
            'explanation' => $isTrue
                ? "Cette affirmation est correcte : {$term} — {$detail}."
                : "Cette affirmation est incorrecte dans le contexte de {$name}.",
            'answers'     => [
                ['text' => 'Vrai', 'correct' => $isTrue],
                ['text' => 'Faux', 'correct' => !$isTrue],
            ],
        ];
    }

    $correctIdx = $seed % 4;
    $answers    = [];
    for ($i = 0; $i < 4; $i++) {
        if ($i === $correctIdx) {
            $answers[] = ['text' => $term, 'correct' => true];
        } else {
            $otherFact = pick($category['facts'], $seed + $i + 1);
            $answers[] = ['text' => $otherFact[0], 'correct' => false];
        }
    }

    return [
        'type'        => 'mcq',
        'text'        => "[{$theme}] Quiz " . ($quizIndex + 1) . " — Quel élément est correctement lié à {$name} ? (variante " . ($questionIndex + 1) . ")",
        'explanation' => "La bonne réponse est « {$term} » : {$detail}.",
        'answers'     => $answers,
    ];
}

if ($verifyOnly) {
    $totalQuizzes    = count($categories) * QUIZZES_PER_CATEGORY;
    $totalQuestions  = $totalQuizzes * QUESTIONS_PER_QUIZ;
    echo "Catégories : " . count($categories) . "\n";
    echo "Quiz par catégorie (générés) : " . QUIZZES_PER_CATEGORY . " (+ quiz existants dans seed.sql)\n";
    echo "Questions par catégorie (générées) : " . (QUIZZES_PER_CATEGORY * QUESTIONS_PER_QUIZ) . " (+ questions existantes)\n";
    echo "Total quiz générés : {$totalQuizzes}\n";
    echo "Total questions générées : {$totalQuestions}\n";
    exit(0);
}

$quizId      = QUIZ_ID_START;
$questionId  = QUESTION_ID_START;
$quizRows    = [];
$questionRows = [];
$answerRows  = [];

foreach ($categories as $catId => $category) {
    for ($q = 0; $q < QUIZZES_PER_CATEGORY; $q++) {
        $quizNum   = $q + 1;
        $theme     = pick($category['themes'], $q);
        $title     = "{$category['name']} — {$theme} (Quiz {$quizNum})";
        $desc      = "Quiz généré sur {$category['name']} : thème {$theme}, niveau {$quizNum}.";
        $quizRows[] = sprintf(
            "(%d, %d, '%s', '%s', 20, 15)",
            $quizId,
            $catId,
            sqlEscape($title),
            sqlEscape($desc)
        );

        for ($i = 0; $i < QUESTIONS_PER_QUIZ; $i++) {
            $question = buildQuestion($catId, $category, $q, $i);
            $questionRows[] = sprintf(
                "(%d, %d, '%s', '%s', 10, '%s', %d)",
                $questionId,
                $quizId,
                $question['type'],
                sqlEscape($question['text']),
                sqlEscape($question['explanation']),
                $i + 1
            );

            foreach ($question['answers'] as $answer) {
                $answerRows[] = sprintf(
                    "(%d, '%s', %d)",
                    $questionId,
                    sqlEscape($answer['text']),
                    $answer['correct'] ? 1 : 0
                );
            }

            $questionId++;
        }

        $quizId++;
    }
}

$out = fopen($outputPath, 'wb');
if ($out === false) {
    fwrite(STDERR, "Impossible d'écrire {$outputPath}\n");
    exit(1);
}

fwrite($out, "-- Bulk seed généré par bin/generate_bulk_seed.php\n");
fwrite($out, "-- Ne pas éditer manuellement : relancer le script pour régénérer.\n");
fwrite($out, "SET NAMES utf8mb4;\n\n");

$writeBatches = static function ($handle, string $header, array $rows, string $columns) {
    $chunks = array_chunk($rows, BATCH_SIZE);
    foreach ($chunks as $index => $chunk) {
        fwrite($handle, $header . ($index > 0 ? " (suite {$index})" : '') . "\n");
        fwrite($handle, "INSERT IGNORE INTO {$columns} VALUES\n");
        fwrite($handle, implode(",\n", $chunk));
        fwrite($handle, ";\n\n");
    }
};

$writeBatches($out, '-- Quiz générés', $quizRows, '`quizzes` (`id`, `category_id`, `title`, `description`, `time_limit`, `xp_reward`)');
$writeBatches($out, '-- Questions générées', $questionRows, '`questions` (`id`, `quiz_id`, `type`, `question_text`, `points`, `explanation`, `sorting_order`)');
$writeBatches($out, '-- Réponses générées', $answerRows, '`answers` (`question_id`, `answer_text`, `is_correct`)');

fclose($out);

$sizeMb = round(filesize($outputPath) / 1024 / 1024, 2);
echo "Fichier généré : {$outputPath}\n";
echo "Taille : {$sizeMb} Mo\n";
echo "Quiz : " . count($quizRows) . " | Questions : " . count($questionRows) . " | Réponses : " . count($answerRows) . "\n";
echo "Par catégorie : " . QUIZZES_PER_CATEGORY . " quiz, " . (QUIZZES_PER_CATEGORY * QUESTIONS_PER_QUIZ) . " questions\n";
