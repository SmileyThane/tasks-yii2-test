<?php

use yii\db\Migration;

/**
 * Handles the creation of table `task_logs`.
 */
class m250905_130354_create_task_logs_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): void
    {
        $this->createTable('task_logs', [
            'id'        => $this->primaryKey(),
            'task_id'   => $this->integer()->notNull(),
            'user_id'   => $this->integer()->null(),
            'operation' => $this->string(32)->notNull(),
            'changes'   => $this->text()->null(),
            'created_at'=> $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_task_logs_task_id', 'task_logs', 'task_id');
        $this->addForeignKey('fk_task_logs_task', 'task_logs', 'task_id', 'tasks', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_task_logs_user', 'task_logs', 'user_id', 'users', 'id', 'SET NULL', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): void
    {
        $this->dropForeignKey('fk_task_logs_task', 'task_logs');
        $this->dropForeignKey('fk_task_logs_user', 'task_logs');
        $this->dropTable('task_logs');
    }
}
