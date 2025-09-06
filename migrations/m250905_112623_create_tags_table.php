<?php

use yii\db\Migration;

/**
 * Handles the creation of table `tags`.
 */
class m250905_112623_create_tags_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): void
    {
        $this->createTable('tags', [
            'id'         => $this->primaryKey(),
            'name'       => $this->string()->notNull(),
            'color'      => $this->string()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('tags_name_index', 'tags', 'name', true);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): void
    {
        $this->dropTable('tags');
    }
}
