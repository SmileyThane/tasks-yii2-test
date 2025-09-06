<?php

use yii\db\Migration;

class m250905_142942_add_token_version_to_users_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('users', 'token_version', $this->integer()->notNull()->defaultValue(0));
        $this->createIndex('idx_users_token_version', 'users', 'token_version');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx_users_token_version', 'users');
        $this->dropColumn('users', 'token_version');
    }
}
