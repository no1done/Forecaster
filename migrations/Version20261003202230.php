<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261003202230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Let one occurrence of a recurring transaction move to another date';
    }

    public function up(Schema $schema): void
    {
        // Renamed rather than recreated, to keep existing one-off amounts.
        $this->addSql('ALTER TABLE recurring_amount_override RENAME TO recurring_occurrence_override');
        $this->addSql('ALTER SEQUENCE recurring_amount_override_id_seq RENAME TO recurring_occurrence_override_id_seq');
        $this->addSql('ALTER INDEX uniq_be5b5d105b71d755655f7053 RENAME TO UNIQ_5EE3071A5B71D755655F7053');
        $this->addSql('ALTER INDEX idx_be5b5d105b71d755 RENAME TO IDX_5EE3071A5B71D755');
        $this->addSql('ALTER TABLE recurring_occurrence_override RENAME CONSTRAINT fk_be5b5d105b71d755 TO FK_5EE3071A5B71D755');
        $this->addSql('ALTER TABLE recurring_occurrence_override ALTER amount_pence DROP NOT NULL');
        $this->addSql('ALTER TABLE recurring_occurrence_override ADD date DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Date-only changes have no amount to keep.
        $this->addSql('DELETE FROM recurring_occurrence_override WHERE amount_pence IS NULL');
        $this->addSql('ALTER TABLE recurring_occurrence_override DROP date');
        $this->addSql('ALTER TABLE recurring_occurrence_override ALTER amount_pence SET NOT NULL');
        $this->addSql('ALTER TABLE recurring_occurrence_override RENAME CONSTRAINT FK_5EE3071A5B71D755 TO fk_be5b5d105b71d755');
        $this->addSql('ALTER INDEX IDX_5EE3071A5B71D755 RENAME TO idx_be5b5d105b71d755');
        $this->addSql('ALTER INDEX UNIQ_5EE3071A5B71D755655F7053 RENAME TO uniq_be5b5d105b71d755655f7053');
        $this->addSql('ALTER SEQUENCE recurring_occurrence_override_id_seq RENAME TO recurring_amount_override_id_seq');
        $this->addSql('ALTER TABLE recurring_occurrence_override RENAME TO recurring_amount_override');
    }
}
