<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250901142434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Добавление VIEW и индексов для отчетов';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE OR REPLACE VIEW active_users_by_role AS
        SELECT json_array_elements_text(roles) AS role, COUNT(*) AS count
        FROM "user"
        WHERE is_active = true
        GROUP BY role
    ');

        $this->addSql('CREATE OR REPLACE VIEW blocked_users AS
        SELECT id, email, roles, created_at
        FROM "user"
        WHERE is_active = false
    ');

        $this->addSql('CREATE OR REPLACE VIEW active_sessions AS
        SELECT s.id, s.token, s.expires_at, u.email
        FROM session s
        JOIN "user" u ON s.app_user_id = u.id
        WHERE s.expires_at > NOW()
    ');

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_user_is_active ON "user"(is_active)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_token_expires_at ON token(expires_at)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_session_expires_at ON session(expires_at)');
    }


    public function down(Schema $schema): void
    {
        $this->addSql('DROP VIEW IF EXISTS active_users_by_role');
        $this->addSql('DROP VIEW IF EXISTS blocked_users');
        $this->addSql('DROP VIEW IF EXISTS active_sessions');

        $this->addSql('DROP INDEX IF EXISTS idx_user_is_active');
        $this->addSql('DROP INDEX IF EXISTS idx_token_expires_at');
        $this->addSql('DROP INDEX IF EXISTS idx_session_expires_at');
    }
}
