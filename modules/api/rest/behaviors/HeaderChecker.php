<?php
declare(strict_types=1);

namespace app\modules\api\rest\behaviors;

use Yii;
use yii\base\ActionFilter;
use yii\web\BadRequestHttpException;

/**
 * Проверяет наличие необходимых заголовков
 */
class HeaderChecker extends ActionFilter
{
    public array $requiredHeaders = [];

    /**
     * @param $action
     * @return bool
     * @throws BadRequestHttpException
     */
    public function beforeAction($action): bool
    {
        $requestHeaders = Yii::$app->request->headers;

        $needsHeader = [];
        foreach ($this->requiredHeaders as $header) {
            if (!$requestHeaders->has($header)) {
                $needsHeader[] = $header;
            }
        }
        if ([] !== $needsHeader) {
            throw new BadRequestHttpException(
                "В запросе отсутствуют обязательные заголовки:" . implode(',', $needsHeader)
            );
        }

        return parent::beforeAction($action);
    }
}
