<?php

declare(strict_types=1);

namespace app\modules\api\rest;

use Yii;
use yii\base\Module as YiiBaseModule;
use yii\web\Response;

/**
 * Class ApiModule
 * @package app\modules\api
 */
class ApiModule extends YiiBaseModule
{
    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        Yii::$app->request->enableCsrfCookie = false;
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->user->enableSession = false;
    }
}
