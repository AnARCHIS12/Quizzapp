-- Seed SQL Data - Quizzapp
-- Injects default roles, achievements, initial categories, and highly neutral quiz contents.

SET NAMES utf8mb4;

-- 1. Insert Roles
INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'admin', 'Administrator with full access to management features'),
(2, 'user', 'Standard registered player');

-- 2. Insert Default Achievements
INSERT INTO `achievements` (`id`, `name`, `description`, `badge_image`, `criteria_type`, `criteria_value`) VALUES
(1, 'Premier pas', 'Complétez votre premier quiz.', 'badge_first_quiz.png', 'quizzes_played', 1),
(2, 'Passionné', 'Complétez 10 quiz.', 'badge_10_quizzes.png', 'quizzes_played', 10),
(3, 'Expert', 'Complétez 50 quiz.', 'badge_50_quizzes.png', 'quizzes_played', 50),
(4, 'Nouveau Niveau', 'Atteignez le niveau 5.', 'badge_level_5.png', 'level_reached', 5),
(5, 'Maître du Quiz', 'Atteignez le niveau 10.', 'badge_level_10.png', 'level_reached', 10),
(6, 'Sans Faute', 'Obtenez un score parfait de 100% sur un quiz.', 'badge_perfect_score.png', 'perfect_score', 1);

-- 3. Insert Default Categories (Neutral subjects and politics)
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `description`, `image_url`) VALUES
(1, NULL, 'Astronomie', 'astronomie', 'Découvrez les planètes, étoiles, galaxies et mystères de l\'univers de manière scientifique.', 'astronomy.jpg'),
(2, NULL, 'Géographie', 'geographie', 'Testez vos connaissances sur les pays, capitales, fleuves et montagnes de notre planète.', 'geography.jpg'),
(3, NULL, 'Mathématiques', 'mathematiques', 'Défiez votre esprit logique avec de l\'algèbre, de la géométrie et du calcul mental.', 'mathematics.jpg'),
(4, NULL, 'Informatique', 'informatique', 'Plongez dans l\'histoire du numérique, la programmation, l\'architecture et les réseaux.', 'computer_science.jpg'),
(5, NULL, 'Histoire', 'histoire', 'Parcourez les grands événements historiques, dynasties et découvertes mondiales de façon factuelle.', 'history.jpg'),
(6, NULL, 'Sciences & Nature', 'sciences-nature', 'Explorez la faune, la flore, les lois de la physique, la chimie et les sciences naturelles.', 'science.jpg'),
(7, NULL, 'Littérature', 'litterature', 'Redécouvrez les grands auteurs, romans classiques, poésies et dramaturges à travers l\'histoire.', 'literature.jpg'),
(8, NULL, 'Cinéma', 'cinema', 'Évaluez votre culture sur les chefs-d\'œuvre du 7ème art, réalisateurs, acteurs et techniques cinématographiques.', 'cinema.jpg'),
(9, NULL, 'Art & Peinture', 'art-peinture', 'Voyagez à travers les courants artistiques, les grands peintres, sculpteurs et musées célèbres.', 'art.jpg'),
(10, NULL, 'Mythologie', 'mythologie', 'Explorez les récits légendaires, panthéons grecs, romains, nordiques, égyptiens et contes anciens.', 'mythology.jpg'),
(11, NULL, 'Politique', 'politique', 'Explorez les théories, régimes, institutions et l\'histoire de la pensée politique.', 'politics.jpg'),
(12, 11, 'Socialisme', 'socialisme', 'Doctrine politique et économique prônant la justice sociale et la propriété collective ou publique des moyens de production.', 'socialism.jpg'),
(13, 11, 'Anarchisme', 'anarchisme', 'Courant de philosophie politique qui rejette toute autorité, État ou hiérarchie sociale au profit d\'une liberté totale.', 'anarchism.jpg'),
(14, 11, 'Communisme', 'communisme', 'Idéologie politique visant l\'instauration d\'une société sans classes, sans État et sans propriété privée.', 'communism.jpg'),
(15, 11, 'Générale (Politique)', 'politique-generale', 'Culture politique générale : systèmes, institutions démocratiques, géopolitique et histoire mondiale.', 'general_politics.jpg');

-- 4. Seed Default Admin and User
-- Admin Password: admin123 (hashed with bcrypt)
-- User Password: user123 (hashed with bcrypt)
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `avatar_url`, `role_id`, `email_verified`) VALUES
(1, 'admin', 'admin@quizapp.com', '$2y$12$RgbjIwrRKq2mJQf8/iaCQOp50rSsGREGu6YOrL6CP0LtgBnOO4Vgq', 'avatar_admin.png', 1, 1),
(2, 'joueur1', 'joueur1@quizapp.com', '$2y$12$Vr2G1IBUrJtWq0IAhTorHe3wVWAoP3N/rq9NZmqBye7jGnMgZv6xS', 'avatar_user.png', 2, 1);

-- Seed User statistics initialized
INSERT INTO `user_statistics` (`user_id`, `level`, `xp`, `total_played`, `correct_count`, `time_spent`, `average_time_per_question`) VALUES
(1, 1, 0, 0, 0, 0, 0),
(2, 1, 0, 0, 0, 0, 0);

-- 5. Seed Quizzes
INSERT INTO `quizzes` (`id`, `category_id`, `title`, `description`, `time_limit`, `xp_reward`) VALUES
(1, 1, 'Notre Système Solaire', 'Un quiz factuel sur les planètes et corps célestes proches de la Terre.', 20, 15),
(2, 1, 'Conquête Spatiale', 'Testez vos connaissances sur l\'histoire des vols spatiaux habités et des sondes robotisées.', 20, 20),
(3, 2, 'Capitales Mondiales', 'Testez vos connaissances géographiques sur les capitales de différents continents.', 20, 10),
(4, 2, 'Fleuves & Montagnes', 'Un quiz sur les reliefs de notre monde, des plus hauts sommets aux plus grands cours d\'eau.', 20, 15),
(5, 3, 'Algèbre & Équations', 'Résolvez des problèmes mathématiques variés et testez votre esprit analytique.', 25, 20),
(6, 4, 'Histoire du Code & Web', 'Découvrez les pionniers de l\'informatique et la création des technologies modernes.', 20, 15),
(7, 5, 'Les Civilisations Antiques', 'Explorez l\'époque romaine, l\'Égypte ancienne, la Grèce et la Mésopotamie.', 20, 20),
(8, 6, 'Biologie & Anatomie', 'Quiz complet sur le corps humain et les mécanismes du vivant.', 20, 15),
(9, 7, 'Littérature Classique', 'De Shakespeare à Molière, en passant par les grands romans français et mondiaux.', 20, 20),
(10, 8, 'Chefs-d\'œuvre du 7ème Art', 'Un quiz complet pour cinéphiles, sur les films culte et l\'histoire de la réalisation.', 20, 15),
(11, 9, 'Peintres & Mouvements', 'De la Renaissance au cubisme, testez vos connaissances sur l\'histoire de l\'art pictural.', 20, 20),
(12, 10, 'Mythes & Légendes Antiques', 'Plongez dans les légendes et généalogies des divinités grecques, égyptiennes et nordiques.', 20, 15),
(13, 12, 'Fondations et Courants Socialistes', 'Testez vos connaissances sur l\'histoire des luttes sociales, des coopératives et des grands penseurs socialistes.', 20, 15),
(14, 13, 'Philosophies et Figures de l\'Anarchisme', 'Parcourez les théories libertaires, les mouvements d\'autogestion et les penseurs du rejet de l\'État.', 20, 15),
(15, 14, 'Histoire et Théorie du Communisme', 'Un parcours factuel sur les révolutions, les écrits marxistes et les régimes du XXe siècle.', 20, 15),
(16, 15, 'Systèmes et Idées Politiques', 'Testez votre culture politique générale sur les régimes, la géopolitique et la séparation des pouvoirs.', 20, 15);

-- QUESTIONS & ANSWERS (5 QUESTIONS PER QUIZ = 60 QUESTIONS TOTAL)

-- Quiz 1: Notre Système Solaire (Astronomie)
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `question_text`, `points`, `explanation`, `sorting_order`) VALUES
(1, 1, 'mcq', 'Quelle est la planète la plus proche du Soleil dans notre système solaire ?', 10, 'Mercure est la première planète du système solaire par ordre de distance au Soleil.', 1),
(2, 1, 'true_false', 'Jupiter est la plus grande planète de notre système solaire.', 10, 'Jupiter a un diamètre équatorial d\'environ 142 984 kilomètres, soit plus de 11 fois celui de la Terre.', 2),
(3, 1, 'mcq', 'Quel est l\'âge approximatif de notre Système Solaire ?', 10, 'Le Système Solaire s\'est formé il y a environ 4,568 milliards d\'années.', 3),
(4, 1, 'mcq', 'Quelle planète est communément appelée "la planète rouge" ?', 10, 'Mars doit sa couleur rouge caractéristique à l\'abondance d\'oxyde de fer (rouille) à sa surface.', 4),
(5, 1, 'true_false', 'La Lune produit sa propre lumière visible.', 10, 'La Lune ne produit pas de lumière visible propre; elle ne fait que réfléchir la lumière provenant du Soleil.', 5);

INSERT INTO `answers` (`question_id`, `answer_text`, `is_correct`) VALUES
(1, 'Vénus', 0), (1, 'Mercure', 1), (1, 'La Terre', 0), (1, 'Mars', 0),
(2, 'Vrai', 1), (2, 'Faux', 0),
(3, '1,5 milliard d\'années', 0), (3, '4,6 milliards d\'années', 1), (3, '10 milliards d\'années', 0), (3, '13,8 milliards d\'années', 0),
(4, 'Mercure', 0), (4, 'Vénus', 0), (4, 'Mars', 1), (4, 'Jupiter', 0),
(5, 'Vrai', 0), (5, 'Faux', 1);

-- Quiz 2: Conquête Spatiale (Astronomie)
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `question_text`, `points`, `explanation`, `sorting_order`) VALUES
(6, 2, 'mcq', 'Qui est le premier être humain à avoir effectué un vol dans l\'espace ?', 10, 'Le Soviétique Youri Gagarine a réalisé un vol orbital de 108 minutes le 12 avril 1961.', 1),
(7, 2, 'true_false', 'La mission Apollo 11 a aluni avec succès en juillet 1969.', 10, 'Neil Armstrong et Buzz Aldrin ont aluni le 20 juillet 1969.', 2),
(8, 2, 'mcq', 'Quel pays a lancé le premier satellite artificiel de l\'histoire, Spoutnik 1, en 1957 ?', 10, 'Spoutnik 1 a été mis en orbite par l\'Union Soviétique le 4 octobre 1957.', 3),
(9, 2, 'mcq', 'Quelle est la première femme à être allée dans l\'espace ?', 10, 'Valentina Terechkova a effectué un vol de 3 jours dans l\'espace en juin 1963 à bord de Vostok 6.', 4),
(10, 2, 'true_false', 'La Station Spatiale Internationale (ISS) fait le tour de la Terre en environ 90 minutes.', 10, 'L\'ISS se déplace à environ 28 000 km/h et boucle une orbite complète en 90 à 93 minutes.', 5);

INSERT INTO `answers` (`question_id`, `answer_text`, `is_correct`) VALUES
(6, 'Alan Shepard', 0), (6, 'Youri Gagarine', 1), (6, 'Neil Armstrong', 0), (6, 'John Glenn', 0),
(7, 'Vrai', 1), (7, 'Faux', 0),
(8, 'États-Unis', 0), (8, 'Union Soviétique', 1), (8, 'Royaume-Uni', 0), (8, 'Allemagne', 0),
(9, 'Sally Ride', 0), (9, 'Valentina Terechkova', 1), (9, 'Mae Jemison', 0), (9, 'Peggy Whitson', 0),
(10, 'Vrai', 1), (10, 'Faux', 0);

-- Quiz 3: Capitales Mondiales (Géographie)
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `question_text`, `points`, `explanation`, `sorting_order`) VALUES
(11, 3, 'mcq', 'Quelle est la capitale du Japon ?', 10, 'Tokyo est la capitale officielle du Japon depuis le transfert de la cour impériale depuis Kyoto en 1869.', 1),
(12, 3, 'true_false', 'Sydney est la capitale de l\'Australie.', 10, 'La capitale de l\'Australie est Canberra. Sydney est la ville la plus peuplée du pays, mais pas sa capitale.', 2),
(13, 3, 'mcq', 'Quelle est la capitale fédérale du Canada ?', 10, 'Ottawa a été désignée capitale du Canada par la reine Victoria en 1857.', 3),
(14, 3, 'mcq', 'Quelle est la capitale du Brésil, inaugurée en 1960 ?', 10, 'Brasilia a remplacé Rio de Janeiro pour favoriser le développement de l\'intérieur du pays.', 4),
(15, 3, 'true_false', 'L\'Afrique du Sud possède trois capitales officielles.', 10, 'C\'est vrai : Pretoria (administrative), Le Cap (législative) et Bloemfontein (judiciaire).', 5);

INSERT INTO `answers` (`question_id`, `answer_text`, `is_correct`) VALUES
(11, 'Kyoto', 0), (11, 'Osaka', 0), (11, 'Tokyo', 1), (11, 'Hiroshima', 0),
(12, 'Vrai', 0), (12, 'Faux', 1),
(13, 'Toronto', 0), (13, 'Montréal', 0), (13, 'Ottawa', 1), (13, 'Vancouver', 0),
(14, 'Rio de Janeiro', 0), (14, 'São Paulo', 0), (14, 'Brasilia', 1), (14, 'Salvador', 0),
(15, 'Vrai', 1), (15, 'Faux', 0);

-- Quiz 4: Fleuves & Montagnes (Géographie)
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `question_text`, `points`, `explanation`, `sorting_order`) VALUES
(16, 4, 'mcq', 'Quel fleuve est traditionnellement considéré comme le plus long du monde ?', 10, 'Le Nil mesure environ 6 650 kilomètres, bien que l\'Amazone ait un débit largement supérieur.', 1),
(17, 4, 'true_false', 'Le Mont Blanc est le plus haut sommet du monde.', 10, 'Le plus haut sommet du monde est le Mont Everest (8 848 m). Le Mont Blanc est le plus haut d\'Europe occidentale (4 808 m).', 2),
(18, 4, 'mcq', 'Dans quelle chaîne de montagnes se situe le Mont Everest ?', 10, 'L\'Everest se situe dans l\'Himalaya, à la frontière entre le Népal et la Chine.', 3),
(19, 4, 'mcq', 'Quel fleuve traverse la ville de Paris en France ?', 10, 'La Seine traverse Paris, divisant la ville entre la Rive gauche et la Rive droite.', 4),
(20, 4, 'mcq', 'Quel est le plus grand lac d\'eau douce du monde par sa superficie ?', 10, 'Le lac Supérieur en Amérique du Nord est le plus grand lac d\'eau douce avec 82 100 km².', 5);

INSERT INTO `answers` (`question_id`, `answer_text`, `is_correct`) VALUES
(16, 'L\'Amazone', 0), (16, 'Le Nil', 1), (16, 'Le Mississippi', 0), (16, 'Le Yangzi Jiang', 0),
(17, 'Vrai', 0), (17, 'Faux', 1),
(18, 'Les Andes', 0), (18, 'Les Alpes', 0), (18, 'L\'Himalaya', 1), (18, 'Les Rocheuses', 0),
(19, 'Le Rhône', 0), (19, 'La Loire', 0), (19, 'La Seine', 1), (19, 'La Garonne', 0),
(20, 'Le lac Victoria', 0), (20, 'Le lac Supérieur', 1), (20, 'Le lac Baïkal', 0), (20, 'Le lac Tanganyika', 0);

-- Quiz 5: Algèbre & Équations (Mathématiques)
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `question_text`, `points`, `explanation`, `sorting_order`) VALUES
(21, 5, 'mcq', 'Quelle est la valeur de x dans l\'équation : 3x - 7 = 11 ?', 10, '3x = 11 + 7 => 3x = 18 => x = 18 / 3 = 6.', 1),
(22, 5, 'mcq', 'Quel est le résultat de la racine carrée de 144 ?', 10, '12 multiplié par 12 est égal à 144.', 2),
(23, 5, 'true_false', 'Un triangle dont les côtés mesurent 3 cm, 4 cm et 5 cm est rectangle.', 10, 'Selon le théorème de Pythagore, 3² + 4² = 9 + 16 = 25 = 5². Le triangle est donc bien rectangle.', 3),
(24, 5, 'mcq', 'Si x² = 81, quelle est la solution positive pour x ?', 10, '9 est le nombre positif dont le carré est égal à 81.', 4),
(25, 5, 'true_false', 'Le nombre Pi (π) est un nombre rationnel.', 10, 'Pi est un nombre irrationnel. Il ne peut pas s\'écrire sous la forme d\'une fraction de deux nombres entiers.', 5);

INSERT INTO `answers` (`question_id`, `answer_text`, `is_correct`) VALUES
(21, 'x = 4', 0), (21, 'x = 5', 0), (21, 'x = 6', 1), (21, 'x = 7', 0),
(22, '10', 0), (22, '11', 0), (22, '12', 1), (22, '14', 0),
(23, 'Vrai', 1), (23, 'Faux', 0),
(24, '7', 0), (24, '8', 0), (24, '9', 1), (24, '10', 0),
(25, 'Vrai', 0), (25, 'Faux', 1);

-- Quiz 6: Histoire du Code & Web (Informatique)
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `question_text`, `points`, `explanation`, `sorting_order`) VALUES
(26, 6, 'mcq', 'Qui est considéré comme le tout premier programmeur informatique de l\'histoire ?', 10, 'Ada Lovelace a écrit le premier algorithme destiné à être exécuté par la machine analytique de Charles Babbage.', 1),
(27, 6, 'mcq', 'Qui a inventé le World Wide Web (WWW) au CERN en 1989 ?', 10, 'Le physicien britannique Tim Berners-Lee a conçu le Web pour faciliter le partage d\'informations.', 2),
(28, 6, 'mcq', 'Quel langage de programmation orienté objet a été créé par James Gosling chez Sun Microsystems en 1995 ?', 10, 'Java a été conçu à l\'origine pour être portable sur différents types d\'appareils ("Write once, run anywhere").', 3),
(29, 6, 'true_false', 'Linux est un noyau de système d\'exploitation open-source initié par Linus Torvalds.', 10, 'Linus Torvalds a lancé le développement du noyau Linux en 1991 sous licence libre GPL.', 4),
(30, 6, 'mcq', 'Quel protocole réseau sécurisé chiffre les données échangées entre un navigateur et un site web ?', 10, 'HTTPS (HyperText Transfer Protocol Secure) utilise les protocoles SSL/TLS pour crypter les échanges.', 5);

INSERT INTO `answers` (`question_id`, `answer_text`, `is_correct`) VALUES
(26, 'Alan Turing', 0), (26, 'Ada Lovelace', 1), (26, 'Grace Hopper', 0), (26, 'Dennis Ritchie', 0),
(27, 'Bill Gates', 0), (27, 'Steve Jobs', 0), (27, 'Tim Berners-Lee', 1), (27, 'Alan Turing', 0),
(28, 'Python', 0), (28, 'C++', 0), (28, 'Java', 1), (28, 'Ruby', 0),
(29, 'Vrai', 1), (29, 'Faux', 0),
(30, 'HTTP', 0), (30, 'FTP', 0), (30, 'HTTPS', 1), (30, 'SMTP', 0);

-- Quiz 7: Les Civilisations Antiques (Histoire)
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `question_text`, `points`, `explanation`, `sorting_order`) VALUES
(31, 7, 'mcq', 'En quelle année l\'Empire romain d\'Occident s\'est-il effondré ?', 10, 'La déposition du dernier empereur Romulus Augustule par Odoacre en 476 marque la fin de l\'Antiquité en Occident.', 1),
(32, 7, 'mcq', 'Quelle civilisation a construit la grande pyramide de Gizeh sous la IVe dynastie ?', 10, 'La grande pyramide de Gizeh a été construite pour le pharaon Khéops vers 2560 av. J.-C.', 2),
(33, 7, 'mcq', 'Quelle cité-état grecque était réputée pour sa discipline militaire stricte ?', 10, 'Sparte possédait un système politique et éducatif entièrement focalisé sur la formation des soldats.', 3),
(34, 7, 'true_false', 'Jules César a été le tout premier empereur de l\'Empire romain.', 10, 'Jules César était dictateur à vie, mais c\'est son fils adoptif Auguste (Octave) qui devint le premier empereur romain en 27 av. J.-C.', 4),
(35, 7, 'mcq', 'Quel célèbre recueil de lois babylonien datant d\'environ 1750 av. J.-C. est l\'un des plus anciens textes de lois préservés ?', 10, 'Le Code de Hammurabi est gravé sur une stèle de basalte et édicte des règles basées sur la loi du talion.', 5);

INSERT INTO `answers` (`question_id`, `answer_text`, `is_correct`) VALUES
(31, '395 apr. J.-C.', 0), (31, '476 apr. J.-C.', 1), (31, '1453 apr. J.-C.', 0), (31, '800 apr. J.-C.', 0),
(32, 'Les Sumériens', 0), (32, 'Les Babyloniens', 0), (32, 'Les Égyptiens', 1), (32, 'Les Phéniciens', 0),
(33, 'Athènes', 0), (33, 'Sparte', 1), (33, 'Thèbes', 0), (33, 'Corinthe', 0),
(34, 'Vrai', 0), (34, 'Faux', 1),
(35, 'Les Douze Tables', 0), (35, 'Le Code de Hammurabi', 1), (35, 'Le Code de Justinien', 0), (35, 'La stèle de Rosette', 0);

-- Quiz 8: Biologie & Anatomie (Sciences & Nature)
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `question_text`, `points`, `explanation`, `sorting_order`) VALUES
(36, 8, 'mcq', 'Combien de paires de chromosomes les cellules humaines saines contiennent-elles généralement ?', 10, 'Le génome humain standard comprend 23 paires de chromosomes (soit 46 chromosomes au total).', 1),
(37, 8, 'mcq', 'Quel organe du corps humain filtre les déchets du sang pour produire de l\'urine ?', 10, 'Les reins filtrent le sang pour maintenir l\'équilibre hydrique et éliminer les toxines sous forme d\'urine.', 2),
(38, 8, 'true_false', 'Les globules rouges ont pour rôle principal de transporter l\'oxygène dans tout le corps.', 10, 'Les globules rouges (hématies) contiennent de l\'hémoglobine, qui fixe et transporte l\'oxygène.', 3),
(39, 8, 'mcq', 'Quelle est la plus petite unité structurelle et fonctionnelle du vivant ?', 10, 'La cellule est l\'élément constitutif de base de tous les organismes vivants connus.', 4),
(40, 8, 'mcq', 'Quel pigment végétal donne aux feuilles leur couleur verte et participe à la photosynthèse ?', 10, 'La chlorophylle absorbe la lumière rouge et bleue du soleil pour fabriquer des glucides.', 5);

INSERT INTO `answers` (`question_id`, `answer_text`, `is_correct`) VALUES
(36, '21 paires', 0), (36, '22 paires', 0), (36, '23 paires', 1), (36, '24 paires', 0),
(37, 'Le foie', 0), (37, 'Les reins', 1), (37, 'Le pancréas', 0), (37, 'La rate', 0),
(38, 'Vrai', 1), (38, 'Faux', 0),
(39, 'L\'atome', 0), (39, 'La molécule', 0), (39, 'La cellule', 1), (39, 'Le tissu', 0),
(40, 'Le carotène', 0), (40, 'La chlorophylle', 1), (40, 'La xanthophylle', 0), (40, 'La mélanine', 0);

-- Quiz 9: Littérature Classique (Littérature)
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `question_text`, `points`, `explanation`, `sorting_order`) VALUES
(41, 9, 'mcq', 'Qui a écrit la célèbre tragédie théâtrale "Hamlet" au début du XVIIe siècle ?', 10, 'William Shakespeare a rédigé Hamlet vers 1600. C\'est l\'une de ses pièces les plus jouées.', 1),
(42, 9, 'mcq', 'Qui est l\'auteur du monument de la littérature française "Les Misérables", publié en 1862 ?', 10, 'Victor Hugo a écrit cette fresque sociale qui suit le destin de Jean Valjean.', 2),
(43, 9, 'mcq', 'Quel romancier espagnol a écrit le chef-d\'œuvre "Don Quichotte" en 1605 ?', 10, 'Miguel de Cervantes a créé le personnage de Don Quichotte de la Manche, considéré comme le père du roman moderne.', 3),
(44, 9, 'true_false', '"L\'Étranger" est un célèbre roman philosophique publié par Albert Camus en 1942.', 10, 'Le roman illustre la philosophie de l\'absurde développée par Albert Camus.', 4),
(45, 9, 'mcq', 'Quel dramaturge français a écrit la comédie classique satirique "Le Tartuffe" ?', 10, 'Molière (Jean-Baptiste Poquelin) a dénoncé l\'hypocrisie religieuse dans cette pièce en 1664.', 5);

INSERT INTO `answers` (`question_id`, `answer_text`, `is_correct`) VALUES
(41, 'Victor Hugo', 0), (41, 'William Shakespeare', 1), (41, 'Jean Racine', 0), (41, 'Molière', 0),
(42, 'Émile Zola', 0), (42, 'Gustave Flaubert', 0), (42, 'Victor Hugo', 1), (42, 'Honoré de Balzac', 0),
(43, 'Gabriel García Márquez', 0), (43, 'Miguel de Cervantes', 1), (43, 'Federico García Lorca', 0), (43, 'Lope de Vega', 0),
(44, 'Vrai', 1), (44, 'Faux', 0),
(45, 'Pierre Corneille', 0), (45, 'Jean Racine', 0), (45, 'Molière', 1), (45, 'Marivaux', 0);

-- Quiz 10: Chefs-d'œuvre du 7ème Art (Cinéma)
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `question_text`, `points`, `explanation`, `sorting_order`) VALUES
(46, 10, 'mcq', 'Quel film dramatique policier a remporté l\'Oscar du meilleur film en 1973 ?', 10, 'Le Parrain (The Godfather), réalisé par Francis Ford Coppola, est un chef-d\'œuvre absolu de l\'histoire du cinéma.', 1),
(47, 10, 'mcq', 'Qui a réalisé les films de science-fiction "Interstellar" et "Inception" ?', 10, 'Le cinéaste britanno-américain Christopher Nolan est célèbre pour ses intrigues temporelles complexes.', 2),
(48, 10, 'mcq', 'Quel long-métrage de 1939 contient la réplique culte : "Franchement, ma chère, c\'est le cadet de mes soucis" ?', 10, 'Cette phrase est prononcée par Rhett Butler (Clark Gable) à la fin de "Autant en emporte le vent".', 3),
(49, 10, 'true_false', '"Blanche-Neige et les Sept Nains" (1937) est le premier long-métrage d\'animation de Walt Disney.', 10, 'Le film a marqué l\'histoire comme le premier dessin animé de long format parlant et en couleur.', 4),
(50, 10, 'mcq', 'Qui a réalisé le chef-d\'œuvre du thriller "Psychose" en 1960 ?', 10, 'Alfred Hitchcock, surnommé le "maître du suspense", a réalisé ce classique de l\'épouvante.', 5);

INSERT INTO `answers` (`question_id`, `answer_text`, `is_correct`) VALUES
(46, 'Le Parrain', 1), (46, 'Les Dents de la mer', 0), (46, 'Citizen Kane', 0), (46, 'Apocalypse Now', 0),
(47, 'Steven Spielberg', 0), (47, 'Christopher Nolan', 1), (47, 'Ridley Scott', 0), (47, 'James Cameron', 0),
(48, 'Casablanca', 0), (48, 'Autant en emporte le vent', 1), (48, 'Citizen Kane', 0), (48, 'Le Magicien d\'Oz', 0),
(49, 'Vrai', 1), (49, 'Faux', 0),
(50, 'Stanley Kubrick', 0), (50, 'Orson Welles', 0), (50, 'Alfred Hitchcock', 1), (50, 'Billy Wilder', 0);

-- Quiz 11: Peintres & Mouvements (Art & Peinture)
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `question_text`, `points`, `explanation`, `sorting_order`) VALUES
(51, 11, 'mcq', 'Qui a peint le célèbre tableau "La Nuit étoilée" en 1889 ?', 10, 'La Nuit étoilée a été peinte par Vincent van Gogh alors qu\'il résidait à l\'asile de Saint-Rémy-de-Provence.', 1),
(52, 11, 'mcq', 'Quel artiste de la Renaissance a réalisé les fresques de la voûte de la chapelle Sixtine à Rome ?', 10, 'Michel-Ange a mis quatre ans (1508-1512) pour peindre le plafond de la chapelle.', 2),
(53, 11, 'mcq', 'Quel peintre espagnol est considéré comme l\'un des cofondateurs majeurs du cubisme ?', 10, 'Pablo Picasso a révolutionné la peinture en déstructurant les perspectives avec les Demoiselles d\'Avignon (1907).', 3),
(54, 11, 'true_false', 'Le célébrissime portrait de "La Joconde" (Mona Lisa) est exposé au Musée du Louvre à Paris.', 10, 'Le chef-d\'œuvre de Léonard de Vinci est la pièce maîtresse des collections du musée du Louvre.', 4),
(55, 11, 'mcq', 'Quel peintre français impressionniste est célèbre pour sa série géante de tableaux intitulée "Les Nymphéas" ?', 10, 'Claude Monet a peint près de 250 œuvres représentant le bassin aux nymphéas de sa demeure de Giverny.', 5);

INSERT INTO `answers` (`question_id`, `answer_text`, `is_correct`) VALUES
(51, 'Claude Monet', 0), (51, 'Vincent van Gogh', 1), (51, 'Pablo Picasso', 0), (51, 'Salvador Dalí', 0),
(52, 'Léonard de Vinci', 0), (52, 'Raphaël', 0), (52, 'Michel-Ange', 1), (52, 'Donatello', 0),
(53, 'Salvador Dalí', 0), (53, 'Pablo Picasso', 1), (53, 'Joan Miró', 0), (53, 'Diego Velázquez', 0),
(54, 'Vrai', 1), (54, 'Faux', 0),
(55, 'Pierre-Auguste Renoir', 0), (55, 'Édouard Manet', 0), (55, 'Claude Monet', 1), (55, 'Paul Cézanne', 0);

-- Quiz 12: Mythes & Légendes Antiques (Mythologie)
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `question_text`, `points`, `explanation`, `sorting_order`) VALUES
(56, 12, 'mcq', 'Dans la mythologie grecque, qui est le souverain suprême du mont Olympe et dieu de la foudre ?', 10, 'Zeus règne sur les autres dieux grecs et commande au climat et à la foudre.', 1),
(57, 12, 'mcq', 'Dans la mythologie nordique, quel dieu guerrier manie le redoutable marteau Mjöllnir ?', 10, 'Thor, le protecteur d\'Asgard, utilise son marteau magique pour combattre les géants.', 2),
(58, 12, 'mcq', 'Quel demi-dieu légendaire de la mythologie grecque a réalisé les "douze travaux" imposés par Eurysthée ?', 10, 'Héraclès (Hercule pour les Romains) a accompli ces exploits légendaires pour racheter une folie passagère.', 3),
(59, 12, 'true_false', 'Dans les croyances égyptiennes, Osiris est considéré comme le seigneur et juge du royaume des morts.', 10, 'Osiris préside le tribunal divin où est pesé le cœur des défunts pour l\'accès à l\'au-delà.', 4),
(60, 12, 'mcq', 'Quelle est la déesse grecque associée à la beauté, à la sexualité et à l\'amour ?', 10, 'Aphrodite (Vénus romaine) est traditionnellement née de l\'écume de mer de l\'île de Chypre.', 5);

INSERT INTO `answers` (`question_id`, `answer_text`, `is_correct`) VALUES
(56, 'Poséidon', 0), (56, 'Hadès', 0), (56, 'Zeus', 1), (56, 'Apollon', 0),
(57, 'Odin', 0), (57, 'Loki', 0), (57, 'Thor', 1), (57, 'Baldr', 0),
(58, 'Thésée', 0), (58, 'Persée', 0), (58, 'Héraclès', 1), (58, 'Achille', 0),
(59, 'Vrai', 1), (59, 'Faux', 0),
(60, 'Athéna', 0), (60, 'Héra', 0), (60, 'Aphrodite', 1), (60, 'Artémis', 0);

-- Quiz 13: Fondations et Courants Socialistes (Socialisme)
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `question_text`, `points`, `explanation`, `sorting_order`) VALUES
(61, 13, 'mcq', 'Qui est l\'auteur de l\'ouvrage "Le Capital" publié en 1867 ?', 10, 'Karl Marx a rédigé Le Capital pour analyser le mode de production capitaliste.', 1),
(62, 13, 'mcq', 'Dans quel pays a été fondée la Première Internationale en 1864 ?', 10, 'L\'Association internationale des travailleurs a été fondée à Londres, au Royaume-Uni.', 2),
(63, 13, 'mcq', 'Quelle doctrine prône l\'évolution pacifique vers le socialisme via des réformes électorales ?', 10, 'Le réformisme et la social-démocratie moderne favorisent la transition légale et démocratique.', 3),
(64, 13, 'mcq', 'Qui a dirigé la SFIO et fondé le journal L\'Humanité en 1904 ?', 10, 'Jean Jaurès fut une figure clé du socialisme républicain français jusqu\'à son assassinat en 1914.', 4),
(65, 13, 'true_false', 'L\'État-providence désigne un système où l\'État assure une protection sociale contre les risques de la vie.', 10, 'L\'État-providence intervient dans les domaines social et économique pour assurer le bien-être général.', 5);

INSERT INTO `answers` (`question_id`, `answer_text`, `is_correct`) VALUES
(61, 'Karl Marx', 1), (61, 'Friedrich Engels', 0), (61, 'Pierre-Joseph Proudhon', 0), (61, 'Adam Smith', 0),
(62, 'Royaume-Uni', 1), (62, 'France', 0), (62, 'Allemagne', 0), (62, 'Suisse', 0),
(63, 'Le réformisme / la social-démocratie', 1), (63, 'Le marxisme-léninisme', 0), (63, 'L\'anarcho-syndicalisme', 0), (63, 'Le néolibéralisme', 0),
(64, 'Jean Jaurès', 1), (64, 'Léon Blum', 0), (64, 'Jules Guesde', 0), (64, 'Georges Clemenceau', 0),
(65, 'Vrai', 1), (65, 'Faux', 0);

-- Quiz 14: Philosophies et Figures de l\'Anarchisme (Anarchisme)
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `question_text`, `points`, `explanation`, `sorting_order`) VALUES
(66, 14, 'mcq', 'Quel philosophe français a écrit "La propriété, c\'est le vol !" en 1840 ?', 10, 'Pierre-Joseph Proudhon a écrit cette célèbre formule dans son mémoire "Qu\'est-ce que la propriété ?".', 1),
(67, 14, 'mcq', 'Qui a théorisé "l\'entraide" comme facteur d\'évolution pour contrer le darwinisme social ?', 10, 'Le prince et scientifique Pierre Kropotkine a écrit "L\'Entraide, un facteur de l\'évolution" en 1902.', 2),
(68, 14, 'mcq', 'Quel mouvement paysan révolutionnaire a lutté en Ukraine sous bannière noire entre 1918 et 1921 ?', 10, 'La Makhnovchtchina, menée par Nestor Makhno, a mis en place des communes autogérées en Ukraine.', 3),
(69, 14, 'mcq', 'Quelle célèbre militante anarchiste féministe a édité le journal de libre pensée Mother Earth ?', 10, 'Emma Goldman fut une théoricienne anarchiste majeure, expulsée des États-Unis vers la Russie en 1919.', 4),
(70, 14, 'true_false', 'La CNT était la principale confédération syndicale d\'orientation anarcho-syndicaliste en Espagne en 1936.', 10, 'La Confederación Nacional del Trabajo (CNT) a joué un rôle moteur dans la révolution sociale espagnole de 1936.', 5);

INSERT INTO `answers` (`question_id`, `answer_text`, `is_correct`) VALUES
(66, 'Pierre-Joseph Proudhon', 1), (66, 'Mikhaïl Bakounine', 0), (66, 'Pierre Kropotkine', 0), (66, 'Karl Marx', 0),
(67, 'Pierre Kropotkine', 1), (67, 'Max Stirner', 0), (67, 'Errico Malatesta', 0), (67, 'Leo Tolstoy', 0),
(68, 'La Makhnovchtchina', 1), (68, 'La révolte de Cronstadt', 0), (68, 'La commune de Canton', 0), (68, 'L\'armée rouge', 0),
(69, 'Emma Goldman', 1), (69, 'Louise Michel', 0), (69, 'Voltairine de Cleyre', 0), (69, 'Lucy Parsons', 0),
(70, 'Vrai', 1), (70, 'Faux', 0);

-- Quiz 15: Histoire et Théorie du Communisme (Communisme)
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `question_text`, `points`, `explanation`, `sorting_order`) VALUES
(71, 15, 'mcq', 'Quel écrit rédigé par Marx et Engels en 1848 débute par "Un spectre hante l\'Europe" ?', 10, 'Le Manifeste du parti communiste présente la conception marxiste de la lutte des classes.', 1),
(72, 15, 'mcq', 'Quelle révolution russe d\'octobre 1917 a mené au renversement du gouvernement provisoire ?', 10, 'La révolution d\'Octobre, dirigée par les Bolcheviks sous Lénine, a instauré le pouvoir des soviets.', 2),
(73, 15, 'mcq', 'Quel terme désigne la planification centralisée de l\'économie soviétique introduite en 1928 ?', 10, 'Les plans quinquennaux fixaient des objectifs de production stricts pour l\'industrie et l\'agriculture.', 3),
(74, 15, 'mcq', 'Qui a dirigé la Longue Marche et proclamé la République populaire de Chine en 1949 ?', 10, 'Mao Zedong fut le président du Parti communiste chinois et le principal dirigeant du pays jusqu\'en 1976.', 4),
(75, 15, 'true_false', 'La Commune de Paris de 1871 a été saluée par Karl Marx comme le premier exemple d\'un pouvoir ouvrier.', 10, 'Dans "La Guerre civile en France", Marx décrit la Commune comme la forme politique enfin trouvée de l\'émancipation ouvrière.', 5);

INSERT INTO `answers` (`question_id`, `answer_text`, `is_correct`) VALUES
(71, 'Le Manifeste du parti communiste', 1), (71, 'Le Capital', 0), (71, 'L\'Idéologie allemande', 0), (71, 'L\'État et la Révolution', 0),
(72, 'La révolution d\'Octobre', 1), (72, 'La révolution de Février', 0), (72, 'La révolution de 1905', 0), (72, 'La Commune de Paris', 0),
(73, 'Le plan quinquennal', 1), (73, 'La NEP (Nouvelle politique économique)', 0), (73, 'L\'autogestion', 0), (73, 'Le mercantilisme', 0),
(74, 'Mao Zedong', 1), (74, 'Deng Xiaoping', 0), (74, 'Sun Yat-sen', 0), (74, 'Tchang Kaï-chek', 0),
(75, 'Vrai', 1), (75, 'Faux', 0);

-- Quiz 16: Systèmes et Idées Politiques (Générale - Politique)
INSERT INTO `questions` (`id`, `quiz_id`, `type`, `question_text`, `points`, `explanation`, `sorting_order`) VALUES
(76, 16, 'mcq', 'Quel philosophe a théorisé la séparation des pouvoirs exécutif, législatif et judiciaire ?', 10, 'Montesquieu a exposé ce principe fondamental des démocraties libérales dans "De l\'esprit des lois" en 1748.', 1),
(77, 16, 'mcq', 'Quel système de gouvernement repose sur l\'exercice de la souveraineté par le peuple ?', 10, 'La démocratie tire son nom des termes grecs "demos" (le peuple) et "kratos" (le pouvoir).', 2),
(78, 16, 'mcq', 'Quel suffrage restreint le droit de vote aux citoyens qui s\'acquittent d\'un certain montant d\'impôts ?', 10, 'Le suffrage censitaire s\'oppose au suffrage universel en limitant le vote selon la richesse.', 3),
(79, 16, 'mcq', 'Quelle institution mondiale a succédé à la Société des Nations en 1945 pour préserver la paix ?', 10, 'L\'Organisation des Nations Unies (ONU) a été établie par la charte de San Francisco.', 4),
(80, 16, 'true_false', 'Dans un régime parlementaire, le gouvernement peut être renversé par le parlement.', 10, 'La responsabilité politique du gouvernement devant l\'assemblée législative est la clé du régime parlementaire.', 5);

INSERT INTO `answers` (`question_id`, `answer_text`, `is_correct`) VALUES
(76, 'Montesquieu', 1), (76, 'Jean-Jacques Rousseau', 0), (76, 'John Locke', 0), (76, 'Thomas Hobbes', 0),
(77, 'La démocratie', 1), (77, 'L\'oligarchie', 0), (77, 'L\'autocratie', 0), (77, 'La ploutocratie', 0),
(78, 'Le suffrage censitaire', 1), (78, 'Le suffrage capacitaire', 0), (78, 'Le suffrage universel', 0), (78, 'Le suffrage indirect', 0),
(79, 'L\'Organisation des Nations Unies', 1), (79, 'L\'OTAN', 0), (79, 'L\'Union européenne', 0), (79, 'Le Conseil de l\'Europe', 0),
(80, 'Vrai', 1), (80, 'Faux', 0);

-- Quiz 17: Culture Politique Générale (catégorie parente Politique)
INSERT INTO `quizzes` (`id`, `category_id`, `title`, `description`, `time_limit`, `xp_reward`) VALUES
(17, 11, 'Culture Politique Générale', 'Un tour d\'horizon des grands courants politiques, des régimes et des penseurs qui ont façonné l\'histoire du monde.', 20, 20);

INSERT INTO `questions` (`id`, `quiz_id`, `type`, `question_text`, `points`, `explanation`, `sorting_order`) VALUES
(81, 17, 'mcq', 'Quel philosophe anglais a théorisé le contrat social dans son ouvrage "Léviathan" (1651) ?', 10, 'Thomas Hobbes voit l\'État comme un monstre froid mais nécessaire pour éviter la guerre de tous contre tous.', 1),
(82, 17, 'mcq', 'Quelle est la différence fondamentale entre un régime présidentiel et un régime parlementaire ?', 10, 'Dans le régime présidentiel, le chef de l\'État n\'est pas responsable devant le parlement (USA). Dans le parlementaire, il l\'est (France, UK).', 2),
(83, 17, 'mcq', 'Quel régime politique fut instauré en France après la Révolution de 1789 ?', 10, 'La Première République française a été proclamée le 21 septembre 1792.', 3),
(84, 17, 'mcq', 'Quel terme désigne un régime où le pouvoir est exercé par une élite restreinte ?', 10, 'L\'oligarchie (du grec « oligos » : peu nombreux) désigne le gouvernement par un petit groupe dominant.', 4),
(85, 17, 'true_false', 'La démocratie directe athénienne au Ve siècle av. J.-C. accordait le vote aux femmes et aux esclaves.', 10, 'La démocratie athénienne était très restreinte : seuls les citoyens mâles libres (environ 10-20% de la population) pouvaient participer.', 5),
(86, 17, 'mcq', 'Quel philosophe a défini l\'État comme ayant le monopole de la violence légitime ?', 10, 'Max Weber a formulé cette définition fondatrice de l\'État moderne dans "Le Savant et le Politique" (1919).', 6),
(87, 17, 'mcq', 'Qu\'est-ce que le fédéralisme en politique ?', 10, 'Le fédéralisme partage la souveraineté entre un gouvernement central et des entités fédérées (États, cantons, Länder).', 7),
(88, 17, 'mcq', 'Quel type de régime combine parti unique, censure totale et contrôle de toutes les sphères de la société ?', 10, 'Le totalitarisme (concept analysé par Hannah Arendt) cherche à contrôler non seulement l\'État mais aussi la société civile et l\'individu.', 8),
(89, 17, 'mcq', 'Dans la théorie politique, que désigne le terme « suffrage universel » ?', 10, 'Le suffrage universel accorde le droit de vote à tous les citoyens adultes sans condition de richesse, sexe ou instruction.', 9),
(90, 17, 'true_false', 'La France est la première démocratie moderne à avoir accordé le droit de vote aux femmes (1944).', 10, 'Non, la Nouvelle-Zélande (1893) fut la première. La France a accordé le droit de vote aux femmes en 1944, bien après de nombreux autres pays.', 10);

INSERT INTO `answers` (`question_id`, `answer_text`, `is_correct`) VALUES
(81, 'Thomas Hobbes', 1), (81, 'John Locke', 0), (81, 'Jean-Jacques Rousseau', 0), (81, 'Montesquieu', 0),
(82, 'Dans le présidentiel, l\'exécutif n\'est pas responsable devant le parlement', 1), (82, 'Dans le parlementaire, le président est élu directement par le peuple', 0), (82, 'Le présidentiel impose un premier ministre fort', 0), (82, 'Dans le parlementaire, il n\'y a pas de parlement élu', 0),
(83, 'La Première République', 1), (83, 'La Monarchie constitutionnelle', 0), (83, 'Le Directoire', 0), (83, 'Le Consulat', 0),
(84, 'L\'oligarchie', 1), (84, 'La théocratie', 0), (84, 'La démocratie représentative', 0), (84, 'La ploutocratie', 0),
(85, 'Faux', 1), (85, 'Vrai', 0),
(86, 'Max Weber', 1), (86, 'Karl Marx', 0), (86, 'Émile Durkheim', 0), (86, 'Carl Schmitt', 0),
(87, 'Un système où la souveraineté est partagée entre gouvernement central et entités fédérées', 1), (87, 'Un système où tout pouvoir appartient à l\'État central', 0), (87, 'Un régime où les citoyens votent directement toutes les lois', 0), (87, 'Un régime d\'union personnelle entre plusieurs monarchies', 0),
(88, 'Le totalitarisme', 1), (88, 'L\'autoritarisme', 0), (88, 'La dictature militaire', 0), (88, 'L\'aristocratie', 0),
(89, 'Le droit de vote accordé à tous les citoyens adultes sans condition', 1), (89, 'Le vote réservé aux propriétaires fonciers', 0), (89, 'Le vote indirect via des grands électeurs', 0), (89, 'Le vote limité aux diplômés universitaires', 0),
(90, 'Faux', 1), (90, 'Vrai', 0);
