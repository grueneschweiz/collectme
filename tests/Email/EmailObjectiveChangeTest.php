<?php

declare(strict_types=1);

namespace Email;

use Collectme\Email\EmailObjectiveChange;
use Collectme\Email\EmailTemplateObjectiveAchieved;
use Collectme\Email\EmailTemplateObjectiveAchievedAndRaised;
use Collectme\Email\EmailTemplateObjectiveAchievedFinal;
use Collectme\Email\EmailTemplateObjectiveAdded;
use Collectme\Email\EmailTemplateObjectiveRaised;
use Collectme\Misc\Settings;
use Collectme\Model\Entities\ActivityLog;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumActivityType;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\EnumMailQueueItemTrigger;
use Collectme\Model\Entities\EnumMessageKey;
use Collectme\Model\Entities\EnumPermission;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\MailQueueItem;
use Collectme\Model\Entities\Objective;
use Collectme\Model\Entities\Role;
use Collectme\Model\Entities\SignatureEntry;
use Collectme\Model\Entities\User;
use PHPUnit\Framework\TestCase;

class EmailObjectiveChangeTest extends TestCase
{
    protected Settings $settingsMock;
    protected EmailObjectiveChange $emailObjectiveChange;
    protected User $user;
    protected MailQueueItem $mailQueueItem;
    protected Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settingsMock = $this->createMock(Settings::class);

        /** @noinspection PhpParamsInspection */
        $this->emailObjectiveChange = new EmailObjectiveChange(
            $this->settingsMock,
            $this->getTemplateMock(EmailTemplateObjectiveAchieved::class),
            $this->getTemplateMock(EmailTemplateObjectiveAchievedFinal::class),
            $this->getTemplateMock(EmailTemplateObjectiveAdded::class),
            $this->getTemplateMock(EmailTemplateObjectiveRaised::class),
            $this->getTemplateMock(EmailTemplateObjectiveAchievedAndRaised::class),
        );

        $cause = new Cause(
            null,
            'test_' . wp_generate_password(),
        );
        $cause->save();

        $user = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'Jane',
            'Doe',
            EnumLang::DE,
            true,
            'EmailObjectiveChangeTest'
        );
        $this->user = $user->save();

        $group = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $this->group = $group->save();

        $role = new Role(
            null,
            $user->uuid,
            $group->uuid,
            EnumPermission::READ_WRITE
        );
        $role->save();

        $mailQueueItem = new MailQueueItem(
            null,
            $group->uuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::SIGNATURE
        );
        $this->mailQueueItem = $mailQueueItem;
    }

    private function getTemplateMock(string $className) {
        $subjects = <<<EOL
Subject1 {{firstName}} {{groupName}}
Subject2 {{firstName}} {{groupName}}
EOL;
        $body = <<<EOL
Body {{firstName}} {{groupName}}
More body text
$className
EOL;

        $mock = $this->createMock($className);
        $mock->method('getSubjectTemplate')->willReturn($subjects);
        $mock->method('getBodyTemplate')->willReturn($body);

        return $mock;
    }

    public function test_getMessage(): void
    {
        $email = clone $this->emailObjectiveChange;
        $email->setUser($this->user);
        $email->prepareFor($this->mailQueueItem);

        $expected = <<<EOL
Body {$this->user->firstName} {$this->group->name}
More body text
Collectme\Email\EmailTemplateObjectiveAchieved
EOL;

        self::assertEquals($expected, $email->getMessage());
    }

    public function test_getToAddr(): void
    {
        $email = clone $this->emailObjectiveChange;
        $email->setUser($this->user);

        self::assertSame($this->user->email, $email->getToAddr());
    }

    public function test_getSubject(): void
    {
        $email = clone $this->emailObjectiveChange;
        $email->setUser($this->user);
        $email->prepareFor($this->mailQueueItem);

        $expected =  [
            "Subject1 {$this->user->firstName} {$this->group->name}",
            "Subject2 {$this->user->firstName} {$this->group->name}",
        ];

        self::assertContains($email->getSubject(), $expected);
    }

    public function test_prepareFor__objectiveAchieved(): void
    {
        $log = new ActivityLog(
            null,
            EnumActivityType::PERSONAL_SIGNATURE,
            123,
            $this->group->causeUuid,
            $this->group->uuid,
        );
        $log->save();

        $objective = new Objective(
            null,
            100,
            $this->group->uuid,
            'EmailObjectiveChangeTest',
        );
        $objective->save();

        $entry = new SignatureEntry(
            null,
            $this->group->uuid,
            $this->user->uuid,
            100,
            $log->uuid
        );
        $entry->save();

        $item = clone $this->mailQueueItem;
        $item->triggerObjType = EnumMailQueueItemTrigger::SIGNATURE;
        $item->triggerObjUuid = $entry->uuid;

        $email = clone $this->emailObjectiveChange;
        $email->setUser($this->user);
        $email->prepareFor($item);

        self::assertStringContainsString(EmailTemplateObjectiveAchieved::class, $email->getMessage());
    }

    public function test_prepareFor__objectiveAchievedFinal(): void
    {
        $log = new ActivityLog(
            null,
            EnumActivityType::PERSONAL_SIGNATURE,
            123,
            $this->group->causeUuid,
            $this->group->uuid,
        );
        $log->save();

        $objective = new Objective(
            null,
            500,
            $this->group->uuid,
            'EmailObjectiveChangeTest',
        );
        $objective->save();

        $entry = new SignatureEntry(
            null,
            $this->group->uuid,
            $this->user->uuid,
            500,
            $log->uuid
        );
        $entry->save();

        $item = clone $this->mailQueueItem;
        $item->triggerObjType = EnumMailQueueItemTrigger::SIGNATURE;
        $item->triggerObjUuid = $entry->uuid;

        $email = clone $this->emailObjectiveChange;
        $email->setUser($this->user);
        $email->prepareFor($item);

        self::assertStringContainsString(EmailTemplateObjectiveAchievedFinal::class, $email->getMessage());
    }

    public function test_prepareFor__objectiveAdded(): void
    {
        $objective = new Objective(
            null,
            100,
            $this->group->uuid,
            'EmailObjectiveChangeTest',
        );
        $objective->save();

        $item = clone $this->mailQueueItem;
        $item->triggerObjType = EnumMailQueueItemTrigger::OBJECTIVE;
        $item->triggerObjUuid = $objective->uuid;

        $email = clone $this->emailObjectiveChange;
        $email->setUser($this->user);
        $email->prepareFor($item);

        self::assertStringContainsString(EmailTemplateObjectiveAdded::class, $email->getMessage());
    }

    public function test_prepareFor__objectiveRaised(): void
    {
        $objective1 = new Objective(
            null,
            100,
            $this->group->uuid,
            'EmailObjectiveChangeTest',
        );
        $objective1->save();

        $objective2 = new Objective(
            null,
            200,
            $this->group->uuid,
            'EmailObjectiveChangeTest',
        );
        $objective2->save();

        $item = clone $this->mailQueueItem;
        $item->triggerObjType = EnumMailQueueItemTrigger::OBJECTIVE;
        $item->triggerObjUuid = $objective2->uuid;

        $email = clone $this->emailObjectiveChange;
        $email->setUser($this->user);
        $email->prepareFor($item);

        self::assertStringContainsString(EmailTemplateObjectiveRaised::class, $email->getMessage());
    }

    public function test_prepareFor__objectiveAchievedAndRaised(): void
    {
        $log = new ActivityLog(
            null,
            EnumActivityType::PERSONAL_SIGNATURE,
            123,
            $this->group->causeUuid,
            $this->group->uuid,
        );
        $log->save();

        $objective1 = new Objective(
            null,
            100,
            $this->group->uuid,
            'EmailObjectiveChangeTest',
            date_create('-1 second'),
        );
        $objective1->save();

        $entry = new SignatureEntry(
            null,
            $this->group->uuid,
            $this->user->uuid,
            100,
            $log->uuid,
            date_create('-1 second'),
        );
        $entry->save();

        $objective2 = new Objective(
            null,
            200,
            $this->group->uuid,
            'EmailObjectiveChangeTest',
        );
        $objective2->save();

        $item = clone $this->mailQueueItem;
        $item->triggerObjType = EnumMailQueueItemTrigger::OBJECTIVE;
        $item->triggerObjUuid = $objective2->uuid;

        $email = clone $this->emailObjectiveChange;
        $email->setUser($this->user);
        $email->prepareFor($item);

        self::assertStringContainsString(EmailTemplateObjectiveAchievedAndRaised::class, $email->getMessage());
    }

    public function test_prepareFor__objectiveRaisedAndAchieved(): void
    {
        $log = new ActivityLog(
            null,
            EnumActivityType::PERSONAL_SIGNATURE,
            123,
            $this->group->causeUuid,
            $this->group->uuid,
        );
        $log->save();

        $objective1 = new Objective(
            null,
            100,
            $this->group->uuid,
            'EmailObjectiveChangeTest',
            date_create('-2 seconds'),
        );
        $objective1->save();

        $objective2 = new Objective(
            null,
            200,
            $this->group->uuid,
            'EmailObjectiveChangeTest',
            date_create('-1 second'),
        );
        $objective2->save();

        $entry = new SignatureEntry(
            null,
            $this->group->uuid,
            $this->user->uuid,
            200,
            $log->uuid,
        );
        $entry->save();

        $item = clone $this->mailQueueItem;
        $item->triggerObjType = EnumMailQueueItemTrigger::SIGNATURE;
        $item->triggerObjUuid = $entry->uuid;

        $email = clone $this->emailObjectiveChange;
        $email->setUser($this->user);
        $email->prepareFor($item);

        self::assertStringContainsString(EmailTemplateObjectiveAchieved::class, $email->getMessage());
    }

    public function test_prepareFor__groupAdded(): void
    {
        $item = clone $this->mailQueueItem;
        $item->triggerObjType = EnumMailQueueItemTrigger::GROUP;
        $item->triggerObjUuid = $item->groupUuid;

        $email = clone $this->emailObjectiveChange;
        $email->setUser($this->user);
        $email->prepareFor($item);

        self::assertNotEmpty($item->deleted);
    }

    public function test_shouldBeSent__queueItemDeleted(): void
    {
        $item = clone $this->mailQueueItem;
        $item->save();
        $item->delete();

        $email = clone $this->emailObjectiveChange;
        $email->setUser($this->user);
        $email->prepareFor($item);

        self::assertFalse($email->shouldBeSent());
    }

    public function test_shouldBeSent__objectiveNotAchieved(): void
    {
        $log = new ActivityLog(
            null,
            EnumActivityType::PERSONAL_SIGNATURE,
            123,
            $this->group->causeUuid,
            $this->group->uuid,
        );
        $log->save();

        $entry = new SignatureEntry(
            null,
            $this->group->uuid,
            $this->user->uuid,
            99,
            $log->uuid
        );
        $entry->save();

        $objective = new Objective(
            null,
            100,
            $this->group->uuid,
            'EmailObjectiveChangeTest',
        );
        $objective->save();

        $item = clone $this->mailQueueItem;
        $item->triggerObjType = EnumMailQueueItemTrigger::SIGNATURE;
        $item->triggerObjUuid = $entry->uuid;

        $email = clone $this->emailObjectiveChange;
        $email->setUser($this->user);
        $email->prepareFor($item);

        self::assertFalse($email->shouldBeSent());
    }

    public function test_shouldBeSent__objectiveAchieved(): void
    {
        $log = new ActivityLog(
            null,
            EnumActivityType::PERSONAL_SIGNATURE,
            123,
            $this->group->causeUuid,
            $this->group->uuid,
        );
        $log->save();

        $entry = new SignatureEntry(
            null,
            $this->group->uuid,
            $this->user->uuid,
            100,
            $log->uuid
        );
        $entry->save();

        $objective = new Objective(
            null,
            100,
            $this->group->uuid,
            'EmailObjectiveChangeTest',
        );
        $objective->save();

        $item = clone $this->mailQueueItem;
        $item->triggerObjType = EnumMailQueueItemTrigger::SIGNATURE;
        $item->triggerObjUuid = $entry->uuid;

        $email = clone $this->emailObjectiveChange;
        $email->setUser($this->user);
        $email->prepareFor($item);

        self::assertTrue($email->shouldBeSent());
    }

    public function test_shouldBeSent__objectiveAddedOrRaisedOrBoth(): void
    {
        $objective = new Objective(
            null,
            100,
            $this->group->uuid,
            'EmailObjectiveChangeTest',
        );
        $objective->save();

        $item = clone $this->mailQueueItem;
        $item->triggerObjType = EnumMailQueueItemTrigger::OBJECTIVE;
        $item->triggerObjUuid = $objective->uuid;

        $email = clone $this->emailObjectiveChange;
        $email->setUser($this->user);
        $email->prepareFor($item);

        self::assertTrue($email->shouldBeSent());
    }
}
