<?php

use yii\db\Migration;

class m250905_113156_create_tasks_tags_pivot extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): void
    {
        $this->createTable('tasks_tags', [
            'task_id' => $this->integer()->notNull(),
            'tag_id'  => $this->integer()->notNull(),
        ]);

        $this->addPrimaryKey('pk_tasks_tags', 'tasks_tags', ['task_id', 'tag_id']);

        $this->addForeignKey('fk_tasks_tags_task', 'tasks_tags', 'task_id', 'tasks', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_tasks_tags_tag',  'tasks_tags', 'tag_id',  'tags',  'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): void
    {
        $this->dropForeignKey('fk_tasks_tags_tag',  'tasks_tags');
        $this->dropForeignKey('fk_tasks_tags_task', 'tasks_tags');
        $this->dropTable('tasks_tags');
    }
}
