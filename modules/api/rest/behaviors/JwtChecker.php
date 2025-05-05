<?php
declare(strict_types=1);

namespace app\modules\api\rest\behaviors;

use Yii;
use yii\base\ActionFilter;
use yii\web\BadRequestHttpException;

/**
 * Проверяет наличие необходимых заголовков
 */
class JwtChecker extends ActionFilter
{
    public array $jwtClaims = [];

    /**
     * @param $action
     * @return bool
     * @throws BadRequestHttpException
     */
    public function beforeAction($action): bool
    {
        $jwt = Yii::$app->request->headers->get('jwt');
        $dataSet = Yii::$app->jwt->getParser()->parse($jwt)->claims();
        if (empty($jwt)) {
            throw new BadRequestHttpException('JWT не может быть пустой');
        }
        $jwtEmptyData = [];
        foreach ($this->jwtClaims as $claim) {
            if (false === $dataSet->has($claim)) {
                $jwtEmptyData[] = $claim;
            }
        }
        if ([] !== $jwtEmptyData) {
            throw new BadRequestHttpException(
                "В JWT отсутствуют обязательные атрибуты:" . implode(',', $jwtEmptyData)
            );
        }

        Yii::$app->remoteIdentifier->setOptions($dataSet);

        return parent::beforeAction($action);
    }
}
