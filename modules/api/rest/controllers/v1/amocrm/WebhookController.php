<?php

declare(strict_types=1);

namespace app\modules\api\rest\controllers\v1\amocrm;

use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Exceptions\AmoCRMMissedTokenException;
use AmoCRM\Exceptions\AmoCRMoAuthApiException;
use AmoCRM\Exceptions\InvalidArgumentException;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\NoteType\CommonNote;
use JsonException;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\rest\Controller;

final class WebhookController extends Controller
{
    /**
     * @throws InvalidConfigException|JsonException
     */
    public function actionIndex(): void
    {
        try {
            $this->processLeads(Yii::$app->request->getBodyParams());
        } catch (Throwable $e) {
            file_put_contents(__DIR__ . '/webhook_log.txt', json_encode(Yii::$app->amocrm->getApiClient()->getAccessToken(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), FILE_APPEND);
            file_put_contents(__DIR__ . '/webhook_log.txt', json_encode($e->getMessage(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), FILE_APPEND);
        }
    }

    /**
     * @throws InvalidArgumentException
     * @throws AmoCRMApiException
     * @throws AmoCRMMissedTokenException
     * @throws AmoCRMoAuthApiException
     */
    private function processLeads(array $data): void
    {
        if (isset($data['leads']['add'])) {
            $this->handleAddedLeads($data['leads']['add']);
        }
        if (isset($data['leads']['update'])) {
            $this->handleUpdatedLeads($data['leads']['update']);
        }
    }

    /**
     * @throws InvalidArgumentException
     * @throws AmoCRMApiException
     * @throws AmoCRMMissedTokenException
     * @throws AmoCRMoAuthApiException
     */
    private function handleAddedLeads(array $leads): void
    {
        foreach ($leads as $lead) {
            $noteText = "Создана сделка: {$lead['name']}. Ответственный: {$lead['responsible_user_id']}. Время добавления: " . date('Y-m-d H:i:s', (int)$lead['created_at']);
            $this->addNoteToLead((int)$lead['id'], $noteText);
        }
    }

    /**
     * @throws InvalidArgumentException
     * @throws AmoCRMApiException
     * @throws AmoCRMMissedTokenException
     * @throws AmoCRMoAuthApiException
     */
    private function handleUpdatedLeads(array $leads): void
    {
        foreach ($leads as $lead) {
            $noteText = "Изменена сделка: {$lead['name']}. Время изменения: " . date('Y-m-d H:i:s', (int)$lead['updated_at']);
            $this->addNoteToLead((int)$lead['id'], $noteText);
        }
    }

    /**
     * @throws InvalidArgumentException
     * @throws AmoCRMApiException
     * @throws AmoCRMMissedTokenException
     * @throws AmoCRMoAuthApiException
     */
    private function addNoteToLead(int $leadId, string $noteText): void
    {
        $note = new CommonNote();
        $note->setEntityId($leadId)->setText($noteText);
        Yii::$app->amocrm->getApiClient()->notes(EntityTypesInterface::LEADS)->addOne($note);
    }
}
