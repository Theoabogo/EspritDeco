<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add stripe_session_id to order table';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf(
            $schema->getTable('order')->hasColumn('stripe_session_id'),
            'Column stripe_session_id already exists.'
        );

        $this->addSql('ALTER TABLE `order` ADD stripe_session_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->skipIf(
            !$schema->getTable('order')->hasColumn('stripe_session_id'),
            'Column stripe_session_id does not exist.'
        );

        $this->addSql('ALTER TABLE `order` DROP COLUMN stripe_session_id');
    }
}
