<?php

declare(strict_types=1);

namespace app\modules\api\rest\controllers\v1\amocrm;

use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\NoteType\CommonNote;
use Yii;
use yii\base\InvalidConfigException;
use yii\rest\Controller;

final class WebhookController extends Controller
{
    /**
     * @throws InvalidConfigException
     */
    public function actionIndex(): void
    {
        $this->processLeads(Yii::$app->request->getBodyParams());
    }

    private function processLeads($data) {
        if (isset($data['leads']['add'])) {
            $this->handleAddedLeads($data['leads']['add']);
        }
        if (isset($data['leads']['update'])) {
            $this->handleUpdatedLeads($data['leads']['update']);
        }
    }

    private function handleAddedLeads(array $leads) {
        foreach ($leads as $lead) {
            $noteText = "Создана сделка: {$lead['name']}. Ответственный: {$lead['responsible_user_id']}. Время добавления: " . date('Y-m-d H:i:s', $lead['created_at']);
            $this->addNoteToLead($lead['id'], $noteText);
        }
    }

    private function handleUpdatedLeads(array $leads) {
        foreach ($leads as $lead) {
            $noteText = "Изменена сделка: {$lead['name']}. Время изменения: " . date('Y-m-d H:i:s', $lead['updated_at']);
            $this->addNoteToLead($lead['id'], $noteText);
        }
    }

    private function addNoteToLead($leadId, $noteText) {
        $note = new CommonNote();
        $note->setEntityId($leadId)->setText($noteText);
        try {
            Yii::$app->amocrm->getApiClient()->notes(EntityTypesInterface::LEADS)->addOne($note);
        } catch (AmoCRMApiException $e) {
            error_log("Ошибка при добавлении заметки: " . $e->getMessage());
        }
    }
}
