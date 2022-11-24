<?php

declare(strict_types=1);

namespace Collectme\Email;


use Collectme\Exceptions\CollectmeDBException;
use Collectme\Exceptions\CollectmeException;
use Collectme\Misc\Settings;
use Collectme\Model\Entities\EnumMailQueueItemTrigger;
use Collectme\Model\Entities\MailQueueItem;
use Collectme\Model\Entities\Objective;
use Collectme\Model\Entities\SignatureEntry;
use Collectme\Model\Entities\User;

class EmailObjectiveChange implements QueuableEmail, Mailable
{
    use UserPlaceholder;
    use GroupPlaceholder;

    private User $user;
    private MailQueueItem $queueItem;
    private EmailTemplate $template;

    public function __construct(
        private readonly Settings $settings,
        private readonly EmailTemplateObjectiveAchieved $templateObjectiveAchieved,
        private readonly EmailTemplateObjectiveAchievedFinal $templateObjectiveAchievedFinal,
        private readonly EmailTemplateObjectiveAdded $templateObjectiveAdded,
        private readonly EmailTemplateObjectiveRaised $templateObjectiveRaised,
        private readonly EmailTemplateObjectiveAchievedAndRaised $templateObjectiveAchievedAndRaised,
    )
    {
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
        return $this->applyReplacements(trim($this->selectSubject()));
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
        $subjects = explode("\n", trim($this->template->getSubjectTemplate()));

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
        return wpautop(
            $this->applyReplacements(
                $this->template->getBodyTemplate()
            )
        );
    }

    /**
     * @throws CollectmeDBException
     */
    public function prepareFor(MailQueueItem $item): void
    {
        $this->queueItem = $item;

        if ($item->triggerObjType === EnumMailQueueItemTrigger::SIGNATURE) {
            $template = $this->hasHighestObjective()
                ? $this->templateObjectiveAchievedFinal
                : $this->templateObjectiveAchieved;
        }

        else if ($item->triggerObjType === EnumMailQueueItemTrigger::OBJECTIVE) {
            $template = match(true) {
                $this->isFirstObjective() => $this->templateObjectiveAdded,
                $this->achievedPreviousObjective() && $this->raisedObjective() => $this->templateObjectiveAchievedAndRaised,
                $this->raisedObjective() => $this->templateObjectiveRaised,
                default => null
            };
        }

        if (isset($template) && $template) {
            $this->template = $template;
            return;
        }

        // case $item->triggerObjType === EnumMailQueueItemTrigger::GROUP
        // case $item->triggerObjType === null
        // and other... :)
        $item->delete();
    }

    /**
     * @throws CollectmeDBException
     * @throws CollectmeException
     */
    public function shouldBeSent(): bool
    {
        if ($this->queueItem->deleted) {
            return false;
        }

        if (! isset($this->template)) {
            return false;
        }

        if (
            $this->template instanceof EmailTemplateObjectiveAchieved
            || $this->template instanceof EmailTemplateObjectiveAchievedFinal
        ) {
            return $this->objectiveIsAchieved();
        }

        return true;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function afterSent(): void
    {
    }

    /**
     * @throws CollectmeDBException
     */
    private function objectiveIsAchieved(): bool
    {
        if ($this->queueItem->triggerObjType !== EnumMailQueueItemTrigger::SIGNATURE) {
            return false;
        }

        $signatureEntry = $this->queueItem->triggerObj();

        if (! $signatureEntry) {
            // the signature entry was deleted meanwhile
            $signatureEntry = SignatureEntry::get($this->queueItem->triggerObjUuid, true);
        }

        $objectives = Objective::findByGroups([$this->queueItem->groupUuid]);

        if (empty($objectives)) {
            return false;
        }

        $existingObjectives = array_filter(
            $objectives,
            static fn(Objective $objective) => $objective->created <= $signatureEntry->created
        );

        $objectiveCounts = array_map(
            static fn(Objective $objective) => $objective->objective,
            $existingObjectives,
        );

        $objective = max($objectiveCounts);
        $signatureCount = $this->queueItem->group()->signatures();

        return $signatureCount >= $objective;
    }

    /**
     * @throws CollectmeDBException
     */
    private function hasHighestObjective(): bool
    {
        $objective = Objective::findHighestOfGroup($this->queueItem->groupUuid);
        if (empty($objective)) {
            return false;
        }

        $objectiveSettings = $this->settings->getObjectives($this->queueItem->group()->causeUuid);
        $enabledObjectives = array_filter(
            $objectiveSettings,
            static fn($setting) => $setting['enabled']
        );
        $enabledObjectivesCounts = array_map(
            static fn($setting) => $setting['objective'],
            $enabledObjectives
        );
        $highestAvailableObjective = max([0, ...$enabledObjectivesCounts]);

        return $objective[0]->objective >= $highestAvailableObjective;
    }

    /**
     * @throws CollectmeDBException
     */
    private function isFirstObjective(): bool
    {
        $objectives = Objective::findByGroups([$this->queueItem->groupUuid]);

        return count($objectives) === 1
            && $objectives[0]->uuid === $this->queueItem->triggerObj()?->uuid;
    }

    /**
     * @throws CollectmeDBException
     */
    private function raisedObjective(): bool
    {
        if ($this->queueItem->triggerObjType !== EnumMailQueueItemTrigger::OBJECTIVE) {
            return false;
        }

        $objective = $this->queueItem->triggerObj();

        if (! $objective) {
            return false;
        }

        $highestOtherObjective = $this->getHighestOtherObjective($objective);

        return $highestOtherObjective?->objective < $objective->objective;
    }

    /**
     * @throws CollectmeDBException
     */
    private function achievedPreviousObjective(): bool
    {
        if ($this->queueItem->triggerObjType !== EnumMailQueueItemTrigger::OBJECTIVE) {
            return false;
        }

        $objective = $this->queueItem->triggerObj();

        if (! $objective) {
            return false;
        }

        $highestOtherObjective = $this->getHighestOtherObjective($objective);
        $signatures = SignatureEntry::totalBeforeDateByGroup($objective->created, $this->queueItem->groupUuid);

        return $highestOtherObjective?->objective <= $signatures;
    }

    /**
     * @throws CollectmeDBException
     */
    private function getHighestOtherObjective(Objective $objective): Objective|null {
        $otherObjectives = array_filter(
            Objective::findByGroups([$objective->groupUuid]),
            static fn(Objective $o) => $o->uuid !== $objective->uuid
        );
        return array_reduce(
            $otherObjectives,
            static fn(?Objective $carry, Objective $o) => $o->objective > $carry?->objective ? $o : $carry
        );
    }
}