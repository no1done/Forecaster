<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261003182653 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow transactions to be hidden from the forecast';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ad_hoc_transaction ADD hidden_from_forecast BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE recurring_transaction ADD hidden_from_forecast BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ad_hoc_transaction DROP hidden_from_forecast');
        $this->addSql('ALTER TABLE recurring_transaction DROP hidden_from_forecast');
    }
}
