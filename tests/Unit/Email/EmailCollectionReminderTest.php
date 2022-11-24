<?php

declare(strict_types=1);

namespace Unit\Email;

use Collectme\Email\EmailCollectionReminder;
use Collectme\Email\EmailTemplateContinueCollecting;
use Collectme\Email\EmailTemplateStartCollecting;
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

class EmailCollectionReminderTest extends TestCase
{
    protected EmailCollectionReminder $emailCollectionReminder;
    protected User $user;
    protected MailQueueItem $mailQueueItem;
    protected Group $group;

    protected function setUp(): void
    {
        parent::setUp();
        $template1 = $this->createMock(EmailTemplateStartCollecting::class);
        $template2 = $this->createMock(EmailTemplateContinueCollecting::class);

        $subjects = <<<EOL
Subject1 {{firstName}} {{groupName}}
Subject2 {{firstName}} {{groupName}}
EOL;
        $body = <<<EOL
Body {{firstName}} {{groupName}}
More body text
EOL;

        $template1->method('getSubjectTemplate')->willReturn($subjects);
        $template2->method('getSubjectTemplate')->willReturn($subjects);

        $template1->method('getBodyTemplate')->willReturn($body);
        $template2->method('getBodyTemplate')->willReturn($body);

        $this->emailCollectionReminder = new EmailCollectionReminder($template1, $template2);

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
            'EmailCollectionReminderTest'
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
            EnumMessageKey::COLLECTION_REMINDER,
            wp_generate_password(64, false),
            null,
            $group->uuid,
            EnumMailQueueItemTrigger::GROUP
        );
        $this->mailQueueItem = $mailQueueItem;
    }


    public function test_getMessage(): void
    {
        $email = clone $this->emailCollectionReminder;
        $email->setUser($this->user);
        $email->prepareFor($this->mailQueueItem);

        $expected = <<<EOL
Body {$this->user->firstName} {$this->group->name}
More body text
EOL;

        self::assertEquals($expected, $email->getMessage());
    }

    public function test_getToAddr(): void
    {
        $email = clone $this->emailCollectionReminder;
        $email->setUser($this->user);

        self::assertSame($this->user->email, $email->getToAddr());
    }

    public function test_getSubject(): void
    {
        $email = clone $this->emailCollectionReminder;
        $email->setUser($this->user);
        $email->prepareFor($this->mailQueueItem);

        $expected =  [
            "Subject1 {$this->user->firstName} {$this->group->name}",
            "Subject2 {$this->user->firstName} {$this->group->name}",
        ];

        self::assertContains($email->getSubject(), $expected);
    }

    public function test_shouldBeSent__queueItemDeleted(): void
    {
        $item = clone $this->mailQueueItem;
        $item->save();
        $item->delete();

        $email = clone $this->emailCollectionReminder;
        $email->setUser($this->user);
        $email->prepareFor($item);

        self::assertFalse($email->shouldBeSent());
    }

    public function test_shouldBeSent__noSignatures(): void
    {
        $email = clone $this->emailCollectionReminder;
        $email->setUser($this->user);
        $email->prepareFor($this->mailQueueItem);

        self::assertTrue($email->shouldBeSent());
    }

    public function test_shouldBeSent__noObjective(): void
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
            123,
            $log->uuid
        );
        $entry->save();

        $email = clone $this->emailCollectionReminder;
        $email->setUser($this->user);
        $email->prepareFor($this->mailQueueItem);

        self::assertFalse($email->shouldBeSent());
    }

    public function test_shouldBeSent__belowObjective(): void
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
            20,
            $log->uuid
        );
        $entry->save();

        $objective = new Objective(
            null,
            100,
            $this->group->uuid,
            'EmailCollectionReminderTest',
        );
        $objective->save();

        $email = clone $this->emailCollectionReminder;
        $email->setUser($this->user);
        $email->prepareFor($this->mailQueueItem);

        self::assertTrue($email->shouldBeSent());
    }

    public function test_shouldBeSent__aboveObjective(): void
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
            'EmailCollectionReminderTest',
        );
        $objective->save();

        $email = clone $this->emailCollectionReminder;
        $email->setUser($this->user);
        $email->prepareFor($this->mailQueueItem);

        self::assertFalse($email->shouldBeSent());
    }

    public function test_afterSent(): void
    {
        $email = clone $this->emailCollectionReminder;
        $email->setUser($this->user);
        $email->prepareFor($this->mailQueueItem);

        MailQueueItem::deleteUnsentByGroup($this->group->uuid);

        $email->afterSent();

        $items = MailQueueItem::findUnsentByGroupAndMsgKey($this->group->uuid, EnumMessageKey::COLLECTION_REMINDER);
        self::assertCount(1, $items);
    }
}
