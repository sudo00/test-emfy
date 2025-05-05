<?php

declare(strict_types=1);

namespace app\components\amocrm;

use AmoCRM\Client\AmoCRMApiClient;
use League\OAuth2\Client\Token\AccessToken;
use Yii;
use yii\base\Component;

final class AmocrmClient extends Component
{
    private AmoCRMApiClient $apiClient;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->apiClient = new AmoCRMApiClient(
            Yii::$app->params['clientId'],
            Yii::$app->params['clientSecret'],
            Yii::$app->params['redirectUri'],
        );

        $this->setAccessToken();
    }

    public function getApiClient(): AmoCRMApiClient
    {
        return $this->apiClient;
    }

    private function setAccessToken(): void
    {
        $accessToken = Yii::$app->cache->get('access_token');
        if (!$accessToken) {
            $refreshToken = Yii::$app->cache->get('refresh_token');
            if (!$refreshToken) {
                $accessToken = $this->apiClient
                    ->getOAuthClient()
                    ->setBaseDomain('danilbolotov.amocrm.ru')
                    ->getAccessTokenByCode(Yii::$app->params['authorization_code']);
            } else {
                $accessToken = $this->apiClient->getOAuthClient()->getAccessTokenByRefreshToken(
                    Yii::$app->cache->get('refresh_token')
                );
            }
            $this->saveTokenToCache($accessToken);
        }

        $this->apiClient->setAccessToken($accessToken);
    }

    public function saveTokenToCache(AccessToken $token): void
    {
        Yii::$app->cache->set('access_token', $token, $token->getExpires());
        Yii::$app->cache->set('refresh_token', $token);
    }
}