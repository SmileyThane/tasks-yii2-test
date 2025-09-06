<?php

use yii\db\Migration;

/**
 * Handles the creation of table `auth_token_blacklist`.
 */
class m250905_134715_create_auth_token_blacklist_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): void
    {
        $this->createTable('auth_token_blacklist', [
            'id'         => $this->primaryKey(),
            'jti'        => $this->string(128)->notNull()->unique(),
            'user_id'    => $this->integer()->null(),
            'exp'        => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_atb_exp', 'auth_token_blacklist', 'exp');
        $this->addForeignKey('fk_atb_user', 'auth_token_blacklist', 'user_id', 'users', 'id', 'SET NULL', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): void
    {
        $this->dropForeignKey('fk_atb_user', 'auth_token_blacklist');
        $this->dropTable('auth_token_blacklist');
    }
}
