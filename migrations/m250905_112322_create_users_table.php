<?php

use yii\db\Migration;

/**
 * Handles the creation of table `users`.
 */
class m250905_112322_create_users_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): void
    {
        $this->createTable('users', [
            'id'         => $this->primaryKey(),
            'name'       => $this->string()->notNull(),
            'email'      => $this->string()->notNull(),
            'role'       => $this->string()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('users_email_index', 'users', 'email', true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): void
    {
        $this->dropTable('users');
    }
}
