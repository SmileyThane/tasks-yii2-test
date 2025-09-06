<?php

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\Query;

class TaskQuery extends ActiveQuery
{
    private bool $withTrashed = false;

    public function active(): self
    {
        return $this->andWhere(['t.deleted_at' => null]);
    }
    public function withTrashed(): self
    {
        $this->withTrashed = true;
        return $this;
    }
    public function onlyTrashed(): self
    {
        $this->andWhere('t.deleted_at IS NOT NULL');
        return $this;
    }
    public function prepare($builder): Query|ActiveQuery|TaskQuery
    {
        if (!$this->withTrashed && !$this->whereHasDeletedAtFilter()) {
            $this->andWhere(['t.deleted_at' => null]);
        }
        return parent::prepare($builder);
    }
    private function whereHasDeletedAtFilter(): bool
    {
        return false;
    }
}
