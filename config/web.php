<?php

declare(strict_types=1);

use app\components\amocrm\AmocrmClient;
use app\models\User;
use app\modules\api\rest\ApiModule;
use yii\caching\FileCache;
use yii\log\FileTarget;
use yii\rest\UrlRule;

$params = require __DIR__ . '/params.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'components' => [
        'user' => [
            'identityClass' => User::class,
            'enableAutoLogin' => true,
        ],
        'request' => [
            'cookieValidationKey' => 'd12312PJYjZKneTiEACMVKQcCe-pUpCa6p5Xw',
        ],
        'cache' => [
            'class' => FileCache::class,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => false,
            'rules' => [
                [
                    'class' => UrlRule::class,
                    'patterns' => [
                        'GET' => 'index',
                    ],
                    'controller' => [
                        'api/v1/amocrm/webhook',
                    ],
                    'pluralize' => false,
                ],
            ],
        ],
        'amocrm' => [
            'class' => AmocrmClient::class,
        ],
    ],
    'params' => $params,
    'modules' => [
        'api' => [
            'class' => ApiModule::class
        ],
    ],
];

return $config;
