<?php

declare(strict_types=1);

namespace app\modules\api\rest\controllers\v1\amocrm;

use AmoCRM\Collections\NotesCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Exceptions\AmoCRMMissedTokenException;
use AmoCRM\Exceptions\AmoCRMoAuthApiException;
use AmoCRM\Exceptions\InvalidArgumentException;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\NoteType\CommonNote;
use AmoCRM\OAuth2\Client\Provider\AmoCRMException;
use JsonException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Throwable;
use Yii;
use yii\base\InvalidConfigException;
use yii\rest\Controller;

final class WebhookController extends Controller
{
    /**
     * @throws InvalidConfigException|JsonException
     */
    public function actionActualizeToken(): void
    {
        try {
            Yii::$app->amocrm->getApiClient();
        } catch (Throwable $e) {
            file_put_contents(__DIR__ . '/webhook_log.txt', json_encode($e->getMessage(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), FILE_APPEND);
        }
    }

    /**
     * @throws InvalidConfigException|JsonException
     */
    public function actionIndex(): void
    {
        try {
            $this->processLeads(Yii::$app->request->getBodyParams());
        } catch (Throwable $e) {
            file_put_contents(__DIR__ . '/webhook_log.txt', json_encode(Yii::$app->amocrm->getApiClient()->getAccessToken(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), FILE_APPEND);
            file_put_contents(__DIR__ . '/webhook_log.txt', json_encode($e->getLastRequestInfo(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), FILE_APPEND);
            file_put_contents(__DIR__ . '/webhook_log.txt', json_encode($e->getMessage(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), FILE_APPEND);
        }
    }

    /**
     * @throws InvalidArgumentException
     * @throws AmoCRMMissedTokenException
     * @throws AmoCRMException
     * @throws IdentityProviderException
     */
    private function processLeads(array $data): void
    {
        if (isset($data['leads'])) {
            if (isset($data['leads']['add'])) {
                $this->handleLeads('add', $data['leads']['add']);
            }
            if (isset($data['leads']['update'])) {
                $this->handleLeads('update', $data['leads']['update']);
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     * @throws AmoCRMMissedTokenException
     * @throws AmoCRMException
     * @throws IdentityProviderException
     */
    private function handleLeads(string $operation, array $leads): void
    {
        foreach ($leads as $lead) {
            $leadId = (int)$lead['id'];
            $noteText = $this->generateLeadNote($operation, $lead);
            $this->addNoteToLead($leadId, $noteText);
        }
    }

    private function generateLeadNote(string $operation, array $lead): string
    {
        $dateTime = date('Y-m-d H:i:s', (int)$lead[($operation === 'add' ? 'created_at' : 'updated_at')]);

        if ($operation === 'add') {
            return "Создана сделка: {$lead['name']}. Ответственный: {$lead['responsible_user_id']}. Время добавления: {$dateTime}";
        }

        return "Изменена сделка: {$lead['name']}. Время изменения: {$dateTime}";
    }


    /**
     * @throws InvalidArgumentException
     * @throws AmoCRMApiException
     * @throws AmoCRMMissedTokenException
     * @throws AmoCRMoAuthApiException
     */
    private function addNoteToLead(int $leadId, string $noteText): void
    {
        try {
            $apiClient = Yii::$app->amocrm->getApiClient();
            $notesCollection = new NotesCollection();
            $commonNote = new CommonNote();

            $commonNote->setEntityId($leadId)->setText($noteText);
            $notesCollection->add($commonNote);

            $notes = $apiClient->notes(EntityTypesInterface::LEADS);
            $notes->add($notesCollection);
        } catch (AmoCRMMissedTokenException | InvalidArgumentException $e) {
            Yii::error("Error adding note to lead {$leadId}: " . $e->getMessage(), __METHOD__);
            throw $e;  // Re-throw the exception for higher-level handling if needed
        }
    }
}
