<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240420085322 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game (id INT AUTO_INCREMENT NOT NULL, tournament_id INT DEFAULT NULL, player1_id INT DEFAULT NULL, player2_id INT DEFAULT NULL, match_date DATE DEFAULT NULL, score_player1 INT DEFAULT NULL, score_player2 INT DEFAULT NULL, status VARCHAR(255) NOT NULL, INDEX IDX_232B318C33D1A3E7 (tournament_id), INDEX IDX_232B318CC0990423 (player1_id), INDEX IDX_232B318CD22CABCD (player2_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE registration (id INT AUTO_INCREMENT NOT NULL, player_id INT DEFAULT NULL, tournament_id INT DEFAULT NULL, registration_date DATE NOT NULL, status VARCHAR(255) NOT NULL, INDEX IDX_62A8A7A799E6F5DF (player_id), INDEX IDX_62A8A7A733D1A3E7 (tournament_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tournament (id INT AUTO_INCREMENT NOT NULL, organizer_id INT DEFAULT NULL, winner_id INT DEFAULT NULL, tournament_name VARCHAR(255) NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, location VARCHAR(255) NOT NULL, descritpion VARCHAR(255) NOT NULL, max_participant INT NOT NULL, status VARCHAR(255) NOT NULL, nom_jeu VARCHAR(255) NOT NULL, INDEX IDX_BD5FB8D9876C4DDA (organizer_id), INDEX IDX_BD5FB8D95DFCD4B8 (winner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, last_name VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, email_address VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C33D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CC0990423 FOREIGN KEY (player1_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CD22CABCD FOREIGN KEY (player2_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE registration ADD CONSTRAINT FK_62A8A7A799E6F5DF FOREIGN KEY (player_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE registration ADD CONSTRAINT FK_62A8A7A733D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournament (id)');
        $this->addSql('ALTER TABLE tournament ADD CONSTRAINT FK_BD5FB8D9876C4DDA FOREIGN KEY (organizer_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE tournament ADD CONSTRAINT FK_BD5FB8D95DFCD4B8 FOREIGN KEY (winner_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C33D1A3E7');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318CC0990423');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318CD22CABCD');
        $this->addSql('ALTER TABLE registration DROP FOREIGN KEY FK_62A8A7A799E6F5DF');
        $this->addSql('ALTER TABLE registration DROP FOREIGN KEY FK_62A8A7A733D1A3E7');
        $this->addSql('ALTER TABLE tournament DROP FOREIGN KEY FK_BD5FB8D9876C4DDA');
        $this->addSql('ALTER TABLE tournament DROP FOREIGN KEY FK_BD5FB8D95DFCD4B8');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE registration');
        $this->addSql('DROP TABLE tournament');
        $this->addSql('DROP TABLE `user`');
    }
}
