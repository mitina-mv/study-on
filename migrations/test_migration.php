<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240320133642 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE test_listener_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE test_listener (id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE lesson RENAME COLUMN seq_number TO Ñ‹Ñseq_number');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE test_listener_id_seq CASCADE');
        $this->addSql('DROP TABLE test_listener');
        $this->addSql('ALTER TABLE lesson RENAME COLUMN Ñ‹Ñseq_number TO seq_number');
    }
}
