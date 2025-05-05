<?php
declare(strict_types=1);

namespace app\modules\api\rest\behaviors;

use Yii;
use yii\base\ActionFilter;
use yii\web\BadRequestHttpException;

/**
 * Проверяет наличие необходимых параметров в теле запроса
 */
class BodyChecker extends ActionFilter
{
    public array $requiredParams = [];

    /**
     * @param $action
     * @return bool
     * @throws BadRequestHttpException
     */
    public function beforeAction($action): bool
    {
        $bodyParams = Yii::$app->request->bodyParams;

        $needsParam = [];
        foreach ($this->requiredParams as $param) {
            if (false === isset($bodyParams[$param])) {
                $needsParam[] = $param;
            }
        }
        if ([] !== $needsParam) {
            throw new BadRequestHttpException(
                "В теле запроса отсутствуют обязательные параметры: " . implode(', ', $needsParam)
            );
        }

        return parent::beforeAction($action);
    }
}
