<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261004093300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Give balances and transactions an owner, so each user sees only their own';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->fetchOne('SELECT EXISTS (SELECT 1 FROM "user")')
                && $this->connection->fetchOne('SELECT EXISTS (SELECT 1 FROM balance_checkpoint UNION ALL SELECT 1 FROM recurring_transaction UNION ALL SELECT 1 FROM ad_hoc_transaction)'),
            'There is data but no user to own it. Create one with app:set-password first.',
        );

        $this->addSql('DROP INDEX idx_9a441b08aa9e377a');
        // Everything stored so far belongs to the one user there has been.
        $this->addSql('ALTER TABLE ad_hoc_transaction ADD owner_id INT DEFAULT NULL');
        $this->addSql('UPDATE ad_hoc_transaction SET owner_id = (SELECT MIN(id) FROM "user")');
        $this->addSql('ALTER TABLE ad_hoc_transaction ALTER owner_id SET NOT NULL');
        $this->addSql('ALTER TABLE ad_hoc_transaction ADD CONSTRAINT FK_9A441B087E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_9A441B087E3C61F9AA9E377A ON ad_hoc_transaction (owner_id, date)');
        $this->addSql('CREATE INDEX IDX_9A441B087E3C61F9 ON ad_hoc_transaction (owner_id)');
        $this->addSql('DROP INDEX uniq_57a3f19caa9e377a');
        $this->addSql('ALTER TABLE balance_checkpoint ADD owner_id INT DEFAULT NULL');
        $this->addSql('UPDATE balance_checkpoint SET owner_id = (SELECT MIN(id) FROM "user")');
        $this->addSql('ALTER TABLE balance_checkpoint ALTER owner_id SET NOT NULL');
        $this->addSql('ALTER TABLE balance_checkpoint ADD CONSTRAINT FK_57A3F19C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_57A3F19C7E3C61F9AA9E377A ON balance_checkpoint (owner_id, date)');
        $this->addSql('CREATE INDEX IDX_57A3F19C7E3C61F9 ON balance_checkpoint (owner_id)');
        $this->addSql('ALTER TABLE recurring_transaction ADD owner_id INT DEFAULT NULL');
        $this->addSql('UPDATE recurring_transaction SET owner_id = (SELECT MIN(id) FROM "user")');
        $this->addSql('ALTER TABLE recurring_transaction ALTER owner_id SET NOT NULL');
        $this->addSql('ALTER TABLE recurring_transaction ADD CONSTRAINT FK_D3509AA67E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_D3509AA67E3C61F9 ON recurring_transaction (owner_id)');
    }

    public function down(Schema $schema): void
    {
        // Without owners, two users' balances on the same day would break the unique date.
        $this->abortIf(
            $this->connection->fetchOne('SELECT COUNT(DISTINCT owner_id) FROM (SELECT owner_id FROM balance_checkpoint UNION SELECT owner_id FROM recurring_transaction UNION SELECT owner_id FROM ad_hoc_transaction) owners') > 1,
            'More than one user has data, which cannot be merged back into one.',
        );

        $this->addSql('ALTER TABLE ad_hoc_transaction DROP CONSTRAINT FK_9A441B087E3C61F9');
        $this->addSql('DROP INDEX IDX_9A441B087E3C61F9AA9E377A');
        $this->addSql('DROP INDEX IDX_9A441B087E3C61F9');
        $this->addSql('ALTER TABLE ad_hoc_transaction DROP owner_id');
        $this->addSql('CREATE INDEX idx_9a441b08aa9e377a ON ad_hoc_transaction (date)');
        $this->addSql('ALTER TABLE balance_checkpoint DROP CONSTRAINT FK_57A3F19C7E3C61F9');
        $this->addSql('DROP INDEX UNIQ_57A3F19C7E3C61F9AA9E377A');
        $this->addSql('DROP INDEX IDX_57A3F19C7E3C61F9');
        $this->addSql('ALTER TABLE balance_checkpoint DROP owner_id');
        $this->addSql('CREATE UNIQUE INDEX uniq_57a3f19caa9e377a ON balance_checkpoint (date)');
        $this->addSql('ALTER TABLE recurring_transaction DROP CONSTRAINT FK_D3509AA67E3C61F9');
        $this->addSql('DROP INDEX IDX_D3509AA67E3C61F9');
        $this->addSql('ALTER TABLE recurring_transaction DROP owner_id');
    }
}
