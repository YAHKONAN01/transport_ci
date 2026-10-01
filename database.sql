-- ============================================================
-- TRANSPORT CI — Base de données
-- Plateforme de comparaison de transports en Côte d'Ivoire
-- ============================================================

CREATE DATABASE IF NOT EXISTS transport_ci CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE transport_ci;

-- Villes
CREATE TABLE villes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    region VARCHAR(100),
    actif TINYINT(1) DEFAULT 1
);

-- Types de transport
CREATE TABLE types_transport (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    icone VARCHAR(10) DEFAULT '🚌'
);

-- Compagnies de transport
CREATE TABLE compagnies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    type_id INT,
    description TEXT,
    telephone VARCHAR(30),
    telephone2 VARCHAR(30),
    email VARCHAR(150),
    adresse TEXT,
    logo_couleur VARCHAR(7) DEFAULT '#f97316',
    note_moyenne DECIMAL(2,1) DEFAULT 0,
    nb_avis INT DEFAULT 0,
    actif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (type_id) REFERENCES types_transport(id)
);

-- Lignes (trajet entre 2 villes)
CREATE TABLE lignes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    compagnie_id INT NOT NULL,
    ville_depart_id INT NOT NULL,
    ville_arrivee_id INT NOT NULL,
    prix DECIMAL(10,0) NOT NULL,
    duree_trajet VARCHAR(30),
    actif TINYINT(1) DEFAULT 1,
    FOREIGN KEY (compagnie_id) REFERENCES compagnies(id) ON DELETE CASCADE,
    FOREIGN KEY (ville_depart_id) REFERENCES villes(id),
    FOREIGN KEY (ville_arrivee_id) REFERENCES villes(id)
);

-- Horaires par ligne
CREATE TABLE horaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ligne_id INT NOT NULL,
    heure_depart TIME NOT NULL,
    jours_semaine VARCHAR(100) DEFAULT 'Tous les jours',
    FOREIGN KEY (ligne_id) REFERENCES lignes(id) ON DELETE CASCADE
);

-- Avis utilisateurs
CREATE TABLE avis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    compagnie_id INT NOT NULL,
    auteur VARCHAR(100) DEFAULT 'Anonyme',
    note TINYINT NOT NULL CHECK (note BETWEEN 1 AND 5),
    commentaire TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (compagnie_id) REFERENCES compagnies(id) ON DELETE CASCADE
);

-- Admin
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    nom VARCHAR(100)
);

-- ============================================================
-- DONNÉES
-- ============================================================

INSERT INTO types_transport (nom, icone) VALUES
('Bus', '🚌'), ('Minibus / Gbaka', '🚐'), ('Train', '🚆'),
('Bateau', '⛴️'), ('Taxi interurbain', '🚖');

INSERT INTO villes (nom, region) VALUES
('Abidjan', 'Lagunes'),
('Bouaké', 'Vallée du Bandama'),
('Yamoussoukro', 'Lacs'),
('Korhogo', 'Poro'),
('San-Pédro', 'Bas-Sassandra'),
('Man', 'Tonkpi'),
('Daloa', 'Haut-Sassandra'),
('Gagnoa', 'Gôh'),
('Abengourou', 'Indénié-Djuablin'),
('Divo', 'Lôh-Djiboua'),
('Odienné', 'Kabadougou'),
('Bondoukou', 'Gontougo'),
('Soubré', 'Nawa'),
('Dimbokro', 'N\'Zi'),
('Adzopé', 'La Mé'),
('Agboville', 'Agnéby-Tiassa'),
('Lakota', 'Lôh-Djiboua'),
('Guiglo', 'Cavally'),
('Séguéla', 'Worodougou'),
('Touba', 'Bafing');

INSERT INTO compagnies (nom, type_id, description, telephone, telephone2, email, adresse, logo_couleur) VALUES
('UTB - Union de Transport de Bouaké', 1,
 'Leader du transport interurbain en Côte d\'Ivoire depuis plus de 30 ans. Flotte moderne, climatisée, avec des départs réguliers vers toutes les grandes villes.',
 '+225 07 00 11 11', '+225 07 00 11 12', 'contact@utb.ci',
 'Gare de Gaulle, Adjamé, Abidjan', '#f97316'),

('STC - Société de Transport de Côte d\'Ivoire', 1,
 'Compagnie nationale offrant des liaisons confortables et fiables. Service ponctuel, bagages inclus, avec possibilité de réservation en ligne.',
 '+225 07 00 22 22', '+225 07 00 22 23', 'info@stc.ci',
 'Gare Nord, Adjamé, Abidjan', '#2563eb'),

('Transland CI', 1,
 'Transport premium avec des bus grande capacité, climatisation, WiFi à bord et système de réservation de siège. Idéal pour les longs trajets.',
 '+225 07 00 33 33', NULL, 'info@translandci.com',
 'Boulevard de Marseille, Treichville, Abidjan', '#16a34a'),

('Allah Bébé Express', 2,
 'Minibus rapides et économiques desservant les villes secondaires et les quartiers difficiles d\'accès. Service flexible et fréquent.',
 '+225 07 00 44 44', '+225 07 00 44 45', NULL,
 'Gare de Gaulle, Adjamé, Abidjan', '#dc2626'),

('CITRANS', 1,
 'Compagnie fiable avec des départs matin et soir vers les principales destinations. Bagages acceptés jusqu\'à 20kg inclus.',
 '+225 07 00 55 55', NULL, 'citrans@gmail.com',
 'Gare Routière d\'Adjamé, Abidjan', '#7c3aed'),

('Abidjan Voyages', 2,
 'Spécialiste des trajets rapides en minibus confortables. Personnel courtois et service clientèle disponible 7j/7.',
 '+225 07 00 66 66', '+225 07 00 66 67', NULL,
 'Yopougon, Abidjan', '#0891b2'),

('KIA Motors Transport', 1,
 'Flotte récente de bus coréens haute qualité. Ponctualité garantie, sièges réservés, climatisation puissante.',
 '+225 07 00 77 77', NULL, 'kia@transport.ci',
 'Zone 4, Marcory, Abidjan', '#b45309'),

('Ivoire Express', 1,
 'Service express pour les voyageurs pressés. Départs toutes les 2 heures vers Bouaké, Yamoussoukro et Korhogo. Confort et rapidité.',
 '+225 07 00 88 88', '+225 07 00 88 89', 'ivoireexpress@ci.com',
 'Adjamé Liberté, Abidjan', '#be185d');

-- Lignes Abidjan → Bouaké
INSERT INTO lignes (compagnie_id, ville_depart_id, ville_arrivee_id, prix, duree_trajet) VALUES
(1, 1, 2, 5000, '5h00'), (2, 1, 2, 5500, '5h30'), (3, 1, 2, 7000, '4h30'),
(5, 1, 2, 4500, '5h30'), (8, 1, 2, 6000, '4h45');

-- Lignes Abidjan → Yamoussoukro
INSERT INTO lignes (compagnie_id, ville_depart_id, ville_arrivee_id, prix, duree_trajet) VALUES
(1, 1, 3, 3500, '3h00'), (2, 1, 3, 4000, '3h30'), (3, 1, 3, 5000, '2h45'),
(4, 1, 3, 3000, '3h30'), (6, 1, 3, 3000, '3h00');

-- Lignes Abidjan → Korhogo
INSERT INTO lignes (compagnie_id, ville_depart_id, ville_arrivee_id, prix, duree_trajet) VALUES
(1, 1, 4, 8000, '8h00'), (2, 1, 4, 8500, '8h30'), (5, 1, 4, 7500, '8h00');

-- Lignes Abidjan → San-Pédro
INSERT INTO lignes (compagnie_id, ville_depart_id, ville_arrivee_id, prix, duree_trajet) VALUES
(1, 1, 5, 6000, '6h00'), (2, 1, 5, 6500, '6h30'), (7, 1, 5, 7500, '5h30');

-- Lignes Abidjan → Man
INSERT INTO lignes (compagnie_id, ville_depart_id, ville_arrivee_id, prix, duree_trajet) VALUES
(1, 1, 6, 7000, '7h00'), (3, 1, 6, 9000, '6h30'), (5, 1, 6, 6500, '7h30');

-- Lignes Abidjan → Daloa
INSERT INTO lignes (compagnie_id, ville_depart_id, ville_arrivee_id, prix, duree_trajet) VALUES
(1, 1, 7, 5000, '4h30'), (2, 1, 7, 5500, '5h00'), (8, 1, 7, 6000, '4h00');

-- Lignes Abidjan → Gagnoa
INSERT INTO lignes (compagnie_id, ville_depart_id, ville_arrivee_id, prix, duree_trajet) VALUES
(1, 1, 8, 4500, '4h00'), (4, 1, 8, 3500, '4h30');

-- Lignes Abidjan → Abengourou
INSERT INTO lignes (compagnie_id, ville_depart_id, ville_arrivee_id, prix, duree_trajet) VALUES
(2, 1, 9, 4000, '3h30'), (6, 1, 9, 3500, '4h00');

-- Lignes Bouaké → Korhogo
INSERT INTO lignes (compagnie_id, ville_depart_id, ville_arrivee_id, prix, duree_trajet) VALUES
(1, 2, 4, 4000, '4h00'), (5, 2, 4, 3500, '4h30');

-- Lignes Yamoussoukro → Bouaké
INSERT INTO lignes (compagnie_id, ville_depart_id, ville_arrivee_id, prix, duree_trajet) VALUES
(1, 3, 2, 2500, '2h00'), (2, 3, 2, 2500, '2h15');

-- Horaires
INSERT INTO horaires (ligne_id, heure_depart, jours_semaine) VALUES
(1,'05:30','Tous les jours'),(1,'07:00','Tous les jours'),(1,'09:00','Tous les jours'),
(1,'11:00','Lun-Sam'),(1,'14:00','Tous les jours'),(1,'17:00','Lun-Ven'),
(2,'06:00','Tous les jours'),(2,'08:30','Tous les jours'),(2,'13:00','Tous les jours'),
(2,'16:00','Lun-Sam'),
(3,'06:30','Tous les jours'),(3,'10:00','Tous les jours'),(3,'15:00','Lun-Sam'),
(4,'06:00','Tous les jours'),(4,'08:00','Tous les jours'),(4,'10:00','Tous les jours'),
(4,'12:00','Tous les jours'),(4,'14:00','Tous les jours'),(4,'16:00','Tous les jours'),
(5,'07:00','Tous les jours'),(5,'11:00','Tous les jours'),(5,'15:00','Lun-Sam'),
(6,'06:00','Tous les jours'),(6,'08:00','Tous les jours'),(6,'12:00','Tous les jours'),(6,'16:00','Lun-Sam'),
(7,'06:30','Tous les jours'),(7,'10:00','Tous les jours'),(7,'14:00','Tous les jours'),
(8,'05:30','Tous les jours'),(8,'09:00','Tous les jours'),(8,'14:00','Lun-Sam'),
(9,'06:00','Tous les jours'),(9,'10:00','Tous les jours'),(9,'15:00','Tous les jours'),
(10,'06:00','Tous les jours'),(10,'11:30','Tous les jours'),(10,'16:00','Lun-Sam'),
(11,'06:00','Tous les jours'),(11,'09:00','Lun-Sam'),
(12,'06:30','Tous les jours'),(12,'10:00','Tous les jours'),
(13,'06:00','Lun-Sam'),(13,'14:00','Lun-Ven'),
(14,'06:00','Tous les jours'),(14,'08:30','Tous les jours'),(14,'14:00','Lun-Sam'),
(15,'06:30','Tous les jours'),(15,'11:00','Lun-Sam'),
(16,'05:30','Tous les jours'),(16,'09:00','Tous les jours'),(16,'14:00','Tous les jours'),
(17,'06:00','Tous les jours'),(17,'10:30','Lun-Sam'),
(18,'07:00','Tous les jours'),(18,'13:00','Lun-Sam');

-- Avis
INSERT INTO avis (compagnie_id, auteur, note, commentaire) VALUES
(1,'Konan Yao',5,'Excellent service, bus toujours à l\'heure. Je recommande vivement !'),
(1,'Aya Brou',4,'Bon confort, personnel sympathique. Légèrement en retard au départ mais arrivé à temps.'),
(2,'Koffi Adjoua',4,'Service fiable et prix raisonnables. Les sièges sont confortables.'),
(2,'Diallo Mamadou',3,'Correct mais peut mieux faire sur la ponctualité. Le bus a attendu 30 min.'),
(3,'Ouattara Seydou',5,'Le meilleur ! WiFi, climatisation parfaite, sièges larges. Un vrai confort !'),
(3,'Coulibaly Fatou',5,'Trajet agréable, chauffeur prudent. Je prends toujours Transland pour aller à Bouaké.'),
(4,'N\'Guessan Pierre',3,'Prix imbattable mais le minibus est vieux. Correct pour les petits budgets.'),
(5,'Traoré Aminata',4,'Bonne compagnie, départs réguliers. Le chauffeur connaît bien la route.'),
(8,'Bamba Ibrahim',5,'Ivoire Express est top ! Départ express, pas d\'attente. Je suis fan.');

-- Recalcul notes
UPDATE compagnies c SET
    note_moyenne = (SELECT ROUND(AVG(note),1) FROM avis WHERE compagnie_id = c.id),
    nb_avis      = (SELECT COUNT(*) FROM avis WHERE compagnie_id = c.id);

-- Admin
INSERT INTO admins (email, mot_de_passe, nom) VALUES
('admin@transport.ci', 'PLACEHOLDER', 'Administrateur');
