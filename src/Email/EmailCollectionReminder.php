<?php

declare(strict_types=1);

namespace Collectme\Email;

use Collectme\Exceptions\CollectmeDBException;
use Collectme\Model\Entities\MailQueueItem;
use Collectme\Model\Entities\Objective;
use Collectme\Model\Entities\User;

class EmailCollectionReminder implements QueuableEmail, Mailable
{
    use UserPlaceholder;
    use GroupPlaceholder;

    private User $user;
    private MailQueueItem $queueItem;
    private EmailTemplate $template;

    public function __construct(
        private readonly EmailTemplateStartCollecting $templateStartCollecting,
        private readonly EmailTemplateContinueCollecting $templateContinueCollecting,
    ) {
    }

    public function getToAddr(): string
    {
        return $this->user->email;
    }

    /**
     * @throws CollectmeDBException
     */
    public function getSubject(): string
    {
        return $this->applyReplacements($this->selectSubject());
    }

    /**
     * @throws CollectmeDBException
     */
    private function applyReplacements(string $msg): string
    {
        $msg = $this->replaceUserPlaceholder(
            $msg,
            $this->user,
        );

        return $this->replaceGroupPlaceholder(
            $msg,
            $this->queueItem->group(),
        );
    }

    private function selectSubject(): string
    {
        $subjects = explode("\n", $this->template->getSubjectTemplate());

        $num = count($subjects);
        if (0 === $num) {
            return '';
        }

        if (1 === $num) {
            return $subjects[0];
        }

        /** @noinspection RandomApiMigrationInspection */
        return $subjects[rand(0, $num - 1)];
    }

    /**
     * @throws CollectmeDBException
     */
    public function getMessage(): string
    {
        return $this->applyReplacements($this->template->getBodyTemplate());
    }

    /**
     * @throws CollectmeDBException
     */
    public function shouldBeSent(): bool
    {
        if ($this->queueItem->deleted) {
            return false;
        }

        if ($this->template instanceof EmailTemplateStartCollecting) {
            return true;
        }

        // only send continue collection reminder if user has an objective
        // and the objective hasn't been met.
        $objective = Objective::findHighestOfGroup($this->queueItem->groupUuid);

        if (empty($objective)) {
            return false;
        }

        return $this->queueItem->group()->signatures() < $objective[0]->objective;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @throws CollectmeDBException
     */
    public function afterSent(): void
    {
        $nextReminder = new MailQueueItem(
            null,
            $this->queueItem->groupUuid,
            $this->queueItem->messageKey,
            wp_generate_password(64, false),
            null,
            $this->queueItem->uuid,
            $this->queueItem->triggerObjType,
        );

        $nextReminder->save();
    }

    /**
     * @throws CollectmeDBException
     */
    public function prepareFor(MailQueueItem $item): void
    {
        $this->queueItem = $item;

        $this->template = $this->hasSignatures()
            ? $this->templateContinueCollecting
            : $this->templateStartCollecting;
    }

    /**
     * @throws CollectmeDBException
     */
    private function hasSignatures(): bool
    {
        return $this->queueItem->group()->signatures() > 0;
    }
}