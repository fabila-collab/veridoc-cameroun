CREATE DATABASE IF NOT EXISTS veridoc;
USE veridoc;
CREATE TABLE etablissements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(150) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  mot_de_passe VARCHAR(255) NOT NULL,
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
  );
CREATE TABLE documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code_unique VARCHAR(50) UNIQUE NOT NULL,
  nom_titulaire VARCHAR(150) NOT NULL,
  type_document VARCHAR(100) NOT NULL,
  etablissement_id INT NOT NULL,
  date_emission DATE NOT NULL,
  statut ENUM('valide' , 'revoque') DEFAULT 'valide',
  date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (etablissement_id) REFERENCES etablissements(id)
  );
