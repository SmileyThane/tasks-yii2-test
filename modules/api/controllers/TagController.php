<?php
namespace app\modules\api\controllers;

use app\models\Tag;
use app\modules\api\Controller;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\web\NotFoundHttpException;

class TagController extends Controller
{

    /**
     * GET /tags — tags list
     */
    public function actionIndex(): array
    {
        return Tag::find()->orderBy(['name' => SORT_ASC])->all();
    }

    /**
     *  POST /tags — create tag
     *  body: { "name": "...", "color": "..." }
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function actionCreate(): Tag|array
    {
        $body = Yii::$app->request->getBodyParams();
        $model = new Tag();
        $model->load($body, '');

        if ($model->save()) {
            Yii::$app->response->statusCode = 201;
            return $model;
        }

        Yii::$app->response->statusCode = 422;
        return ['errors' => $model->getErrors()];
    }

    /**
     * PUT /tags/{id} — update tag
     * body: { "name": "...", "color": "..." }
     * @throws Throwable
     */
    public function actionUpdate(int $id): Tag|array
    {
        $model = $this->findModel($id);
        $body = Yii::$app->request->getBodyParams();
        $model->load($body, '');

        if ($model->save()) {
            return $model;
        }

        Yii::$app->response->statusCode = 422;
        return ['errors' => $model->getErrors()];
    }

    /**
     * DELETE /tags/{id} — delete tag
     * @throws Throwable
     */
    public function actionDelete(int $id): ?array
    {
        $model = $this->findModel($id);
        if ($model->delete() === false) {
            Yii::$app->response->statusCode = 422;
            return ['errors' => $model->getErrors()];
        }
        Yii::$app->response->statusCode = 204;
        return null;
    }

    /**
     * @throws NotFoundHttpException
     */
    protected function findModel(int $id): Tag
    {
        $m = Tag::findOne($id);
        if (!$m) throw new NotFoundHttpException('Tag not found');

        return $m;
    }
}
