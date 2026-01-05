<?php /** @noinspection PhpUnused */

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add roles_last_refreshed column to user table for tracking when Discord roles were last synchronized
 */
final class Version20260104213457 extends AbstractMigration
{
    /** @noinspection SenselessMethodDuplicationInspection */
    public function getDescription(): string
    {
        return 'Add roles_last_refreshed timestamp to user table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD roles_last_refreshed DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP roles_last_refreshed');
    }
}
