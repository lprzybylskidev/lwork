<?php declare(strict_types=1);

namespace src\database\migrations\core;

use Phinx\Migration\AbstractMigration;

final class CreateSessionsTable extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sessions', [
            'id' => false,
            'primary_key' => 'id',
        ]);
        $table
            ->addColumn('id', 'string', ['limit' => 128])
            ->addColumn('payload', 'text')
            ->addColumn('expires_at', 'timestamp', ['null' => true])
            ->addColumn('created_at', 'timestamp', [
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->create();
    }
}
