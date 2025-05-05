<?php
declare(strict_types=1);

namespace app\modules\api\rest\behaviors;

use app\components\RemoteApplication\Auth;
use Exception;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\web\UnauthorizedHttpException;

/**
 * Базовое поведение для авторизации через токен приложения (RemoteApplicationToken)
 */
class PsbHttpBearerAuth extends HttpBearerAuth
{
    private ?Auth $auth = null;

    /**
     * @param $user
     * @param $request
     * @param $response
     * @inheritdoc
     * @throws Exception
     */
    public function authenticate($user, $request, $response)
    {
        $authHeader = $request->getHeaders()->get($this->header);

        if (null !== $authHeader && preg_match($this->pattern, $authHeader, $matches)) {
            $this->auth = (new Auth($matches[1]));
            if (true === $this->auth->verificationToken()) {
                Yii::$app->remoteIdentifier->setApplicationToken($this->auth->getExistentToken());
                return true;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function handleFailure($response): void
    {
        $errorMessage = 'Переданы не верные данные для авторизации';
        if (null !== $this->auth && '' !== $this->auth->getError()) {
            $errorMessage = $this->auth->getError();
        }
        throw new UnauthorizedHttpException($errorMessage);
    }
}
