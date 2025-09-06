<?php

use yii\db\Migration;

/**
 * Handles the creation of table `tasks`.
 */
class m250905_112709_create_tasks_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): void
    {
        $this->createTable('tasks', [
            'id'          => $this->primaryKey(),
            'title'       => $this->string()->notNull(),
            'description' => $this->text()->null(),

            'status'      => $this->string(32)->notNull()->defaultValue('pending'),
            'priority'    => $this->string(16)->notNull()->defaultValue('medium'),
            'due_date'    => $this->date()->null(),
            'assigned_to' => $this->integer()->null(),
            'metadata'    => $this->text()->null(),
            'created_at'  => $this->integer()->notNull(),
            'updated_at'  => $this->integer()->notNull(),
            'deleted_at'  => $this->integer()->null(),

            'version'     => $this->integer()->notNull()->defaultValue(0),
        ]);

        $this->createIndex('tasks_status_index',   'tasks', 'status');
        $this->createIndex('tasks_priority_index', 'tasks', 'priority');
        $this->createIndex('tasks_due_date_index', 'tasks', 'due_date');
        $this->createIndex('tasks_assigned_index', 'tasks', 'assigned_to');
        $this->createIndex('tasks_deleted_index',  'tasks', 'deleted_at');

        $this->addForeignKey(
            'fk_task_assigned_to_user',
            'tasks', 'assigned_to',
            'users', 'id',
            'SET NULL', 'RESTRICT'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): void
    {
        $this->dropForeignKey('fk_task_assigned_to_user', 'tasks');
        $this->dropTable('tasks');
    }
}
