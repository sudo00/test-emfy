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
        var_dump(123);die;
        $rawData = file_get_contents('php://input');
        Yii::error($rawData);
        parse_str($rawData, $data);
        file_put_contents(__DIR__ . '/webhook_log.txt', json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), FILE_APPEND);
//        var_dump(Yii::$app->amocrm->getApiClient());die;
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
