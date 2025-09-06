<?php

use yii\db\Migration;

class m250905_130227_add_auth_fields_to_users_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): void
    {
        $this->addColumn('users', 'password_hash', $this->string()->null());
        $this->addColumn('users', 'auth_key', $this->string()->null());
        $this->createIndex('users_role_index', 'users', 'role', false);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): void
    {
        $this->dropIndex('users_role_index', 'users');
        $this->dropColumn('users', 'auth_key');
        $this->dropColumn('users', 'password_hash');
    }
}
