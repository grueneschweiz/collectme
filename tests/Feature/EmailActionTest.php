<?php

declare(strict_types=1);

namespace Feature;

use Collectme\Email\EmailCollectionReminder;
use Collectme\Email\EmailObjectiveChange;
use Collectme\Email\EmailTemplateContinueCollecting;
use Collectme\Email\EmailTemplateObjectiveAchieved;
use Collectme\Email\EmailTemplateObjectiveAchievedAndRaised;
use Collectme\Email\EmailTemplateObjectiveAchievedFinal;
use Collectme\Email\EmailTemplateObjectiveAdded;
use Collectme\Email\EmailTemplateObjectiveRaised;
use Collectme\Email\EmailTemplateStartCollecting;
use Collectme\Email\Mailable;
use Collectme\Misc\Settings;
use Collectme\Model\Entities\ActivityLog;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumActivityType;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\EnumMessageKey;
use Collectme\Model\Entities\EnumPermission;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\MailQueueItem;
use Collectme\Model\Entities\Objective;
use Collectme\Model\Entities\Role;
use Collectme\Model\Entities\SignatureEntry;
use Collectme\Model\Entities\User;
use PHPUnit\Framework\TestCase;

use const Collectme\DB_PREFIX;

class EmailActionTest extends TestCase
{
    private Cause $cause;
    private Group $group;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $cause = new Cause(
            null,
            'EmailActionTest_'.wp_generate_password(),
        );
        $this->cause = $cause->save();

        $user = new User(
            null,
            wp_generate_uuid4().'@mail.com',
            'Jane',
            'Doe',
            EnumLang::DE,
            true,
            'EmailActionTest'
        );
        $this->user = $user->save();

        $group = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $this->cause->uuid,
            false,
        );
        $this->group = $group->save();

        $role = new Role(
            null,
            $this->user->uuid,
            $this->group->uuid,
            EnumPermission::READ_WRITE
        );
        $role->save();

        $settings = Settings::getInstance();
        $settings->setMailDelays([
            EnumMessageKey::COLLECTION_REMINDER->value => new \DateInterval('PT0S'),
            EnumMessageKey::OBJECTIVE_CHANGE->value => new \DateInterval('PT0S'),
        ], $this->cause->uuid);

        $this->clearQueue();
    }

    private function clearQueue() {
        global $wpdb;
        $tableName = $wpdb->prefix . DB_PREFIX . 'mails';
        $wpdb->query("DELETE FROM $tableName");
    }

    protected function tearDown(): void
    {
        $this->clearQueue();
        remove_all_filters('collectme_email');
    }

    public function test_groupAdded(): void
    {
        $group = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $this->cause->uuid,
            false,
        );
        $this->group = $group->save();

        $role = new Role(
            null,
            $this->user->uuid,
            $this->group->uuid,
            EnumPermission::READ_WRITE
        );
        $role->save();

        $sent = false;
        add_filter('collectme_email', function(Mailable $email) use (&$sent) {
            $template = new \ReflectionProperty(EmailCollectionReminder::class, 'template');
            self::assertInstanceOf(EmailTemplateStartCollecting::class, $template->getValue($email));
            $sent = true;
            return $email;
        });

        $itemsBeforeSend = MailQueueItem::findUnsentByGroupAndMsgKey(
            $this->group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
        );

        do_action('collectme_send_mails');

        $itemsAfterSend = MailQueueItem::findUnsentByGroupAndMsgKey(
            $this->group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
        );

        self::assertCount(1, $itemsBeforeSend);
        self::assertCount(1, $itemsAfterSend);
        self::assertNotEquals($itemsBeforeSend[0]->uuid, $itemsAfterSend[0]->uuid);
        self::assertTrue($sent);
    }

    public function test_groupDeleted(): void
    {
        $group = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $this->cause->uuid,
            false,
        );
        $this->group = $group->save();
        $this->group->delete();

        $role = new Role(
            null,
            $this->user->uuid,
            $this->group->uuid,
            EnumPermission::READ_WRITE
        );
        $role->save();

        $sent = false;
        add_filter('collectme_email', function(Mailable $email) use (&$sent) {
            $sent = true;
            return $email;
        });

        do_action('collectme_send_mails');

        self::assertFalse($sent);
        self::assertEmpty(MailQueueItem::findUnsentByGroup($this->group->uuid));
    }

    public function test_signatureAdded__reminder(): void
    {
        // set new group to test, that we dont have two reminders
        $group = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $this->cause->uuid,
            false,
        );
        $this->group = $group->save();

        $objective = new Objective(
            null,
            200,
            $this->group->uuid,
            'EmailActionTest',
        );
        $objective->save();
        // delete the objective change, we test it separately
        MailQueueItem::deleteUnsentByGroupAndMsgKey($this->group->uuid, EnumMessageKey::OBJECTIVE_CHANGE);

        $role = new Role(
            null,
            $this->user->uuid,
            $this->group->uuid,
            EnumPermission::READ_WRITE
        );
        $role->save();

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

        $sent = false;
        add_filter('collectme_email', function(Mailable $email) use (&$sent) {
            $template = new \ReflectionProperty(EmailCollectionReminder::class, 'template');
            self::assertInstanceOf(EmailTemplateContinueCollecting::class, $template->getValue($email));
            $sent = true;
            return $email;
        });

        $itemsBeforeSend = MailQueueItem::findUnsentByGroupAndMsgKey(
            $this->group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
        );

        do_action('collectme_send_mails');

        $itemsAfterSend = MailQueueItem::findUnsentByGroupAndMsgKey(
            $this->group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
        );

        self::assertCount(1, $itemsBeforeSend);
        self::assertCount(1, $itemsAfterSend);
        self::assertNotEquals($itemsBeforeSend[0]->uuid, $itemsAfterSend[0]->uuid);
        self::assertTrue($sent);
    }

    public function test_signatureDeleted__reminder(): void
    {
        $objective = new Objective(
            null,
            200,
            $this->group->uuid,
            'EmailActionTest',
        );
        $objective->save();
        // delete the objective change, we test it separately
        MailQueueItem::deleteUnsentByGroupAndMsgKey($this->group->uuid, EnumMessageKey::OBJECTIVE_CHANGE);

        $role = new Role(
            null,
            $this->user->uuid,
            $this->group->uuid,
            EnumPermission::READ_WRITE
        );
        $role->save();

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
        $entry->delete();

        $sent = false;
        add_filter('collectme_email', function(Mailable $email) use (&$sent) {
            $sent = true;
            return $email;
        });

        $itemsBeforeSend = MailQueueItem::findUnsentByGroupAndMsgKey(
            $this->group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
        );

        do_action('collectme_send_mails');

        $itemsAfterSend = MailQueueItem::findUnsentByGroupAndMsgKey(
            $this->group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
        );

        self::assertCount(1, $itemsBeforeSend);
        self::assertCount(1, $itemsAfterSend);
        self::assertNotEquals($itemsBeforeSend[0]->uuid, $itemsAfterSend[0]->uuid);
        self::assertTrue($sent);
    }

    public function test_signatureAdded__objectiveAchieved(): void
    {
        $objective = new Objective(
            null,
            100,
            $this->group->uuid,
            'EmailActionTest',
        );
        $objective->save();

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

        $itemsBeforeSend = MailQueueItem::findUnsentByGroupAndMsgKey(
            $this->group->uuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
        );

        $sent = false;
        add_filter('collectme_email', function(Mailable $email) use (&$sent) {
            $template = new \ReflectionProperty(EmailObjectiveChange::class, 'template');
            self::assertInstanceOf(EmailTemplateObjectiveAchieved::class, $template->getValue($email));
            $sent = true;
            return $email;
        });

        do_action('collectme_send_mails');

        $itemsAfterSend = MailQueueItem::findUnsentByGroupAndMsgKey(
            $this->group->uuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
        );

        self::assertCount(1, $itemsBeforeSend);
        self::assertCount(0, $itemsAfterSend);
        self::assertTrue($sent);
    }

    public function test_signatureDeleted__objectiveStillAchieved(): void
    {
        $objective = new Objective(
            null,
            100,
            $this->group->uuid,
            'EmailActionTest',
        );
        $objective->save();
        $this->clearQueue();

        $log = new ActivityLog(
            null,
            EnumActivityType::PERSONAL_SIGNATURE,
            123,
            $this->group->causeUuid,
            $this->group->uuid,
        );
        $log->save();

        $entry1 = new SignatureEntry(
            null,
            $this->group->uuid,
            $this->user->uuid,
            123,
            $log->uuid
        );
        $entry1->save();

        $entry2 = new SignatureEntry(
            null,
            $this->group->uuid,
            $this->user->uuid,
            123,
            $log->uuid
        );
        $entry2->save();
        $entry2->delete();

        $itemsBeforeSend = MailQueueItem::findUnsentByGroupAndMsgKey(
            $this->group->uuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
        );

        $sent = false;
        add_filter('collectme_email', function(Mailable $email) use (&$sent) {
            $template = new \ReflectionProperty(EmailObjectiveChange::class, 'template');
            self::assertInstanceOf(EmailTemplateObjectiveAchieved::class, $template->getValue($email));
            $sent = true;
            return $email;
        });

        do_action('collectme_send_mails');

        $itemsAfterSend = MailQueueItem::findUnsentByGroupAndMsgKey(
            $this->group->uuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
        );

        self::assertCount(1, $itemsBeforeSend);
        self::assertCount(0, $itemsAfterSend);
        self::assertTrue($sent);
    }

    public function test_signatureDeleted__objectiveNotAnymoreAchieved(): void
    {
        $objective = new Objective(
            null,
            100,
            $this->group->uuid,
            'EmailActionTest',
        );
        $objective->save();
        $this->clearQueue();

        $log = new ActivityLog(
            null,
            EnumActivityType::PERSONAL_SIGNATURE,
            123,
            $this->group->causeUuid,
            $this->group->uuid,
        );
        $log->save();

        $entry1 = new SignatureEntry(
            null,
            $this->group->uuid,
            $this->user->uuid,
            10,
            $log->uuid
        );
        $entry1->save();

        $entry2 = new SignatureEntry(
            null,
            $this->group->uuid,
            $this->user->uuid,
            123,
            $log->uuid
        );
        $entry2->save();
        $entry2->delete();

        // this is tested separately
        MailQueueItem::deleteUnsentByGroupAndMsgKey(
            $this->group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
        );

        $sent = false;
        add_filter('collectme_email', function(Mailable $email) use (&$sent) {
            $sent = true;
            return $email;
        });

        do_action('collectme_send_mails');

        self::assertFalse($sent);
    }

    public function test_signatureAdded__objectiveAchievedFinal(): void
    {
        $objective = new Objective(
            null,
            500,
            $this->group->uuid,
            'EmailActionTest',
        );
        $objective->save();

        $log = new ActivityLog(
            null,
            EnumActivityType::PERSONAL_SIGNATURE,
            500,
            $this->group->causeUuid,
            $this->group->uuid,
        );
        $log->save();

        $entry = new SignatureEntry(
            null,
            $this->group->uuid,
            $this->user->uuid,
            500,
            $log->uuid
        );
        $entry->save();

        $itemsBeforeSend = MailQueueItem::findUnsentByGroupAndMsgKey(
            $this->group->uuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
        );

        $sent = false;
        add_filter('collectme_email', function(Mailable $email) use (&$sent) {
            $template = new \ReflectionProperty(EmailObjectiveChange::class, 'template');
            self::assertInstanceOf(EmailTemplateObjectiveAchievedFinal::class, $template->getValue($email));
            $sent = true;
            return $email;
        });

        do_action('collectme_send_mails');

        $itemsAfterSend = MailQueueItem::findUnsentByGroupAndMsgKey(
            $this->group->uuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
        );

        self::assertCount(1, $itemsBeforeSend);
        self::assertCount(0, $itemsAfterSend);
        self::assertTrue($sent);
    }

    public function test_objectiveAdded(): void
    {
        $objective = new Objective(
            null,
            500,
            $this->group->uuid,
            'EmailActionTest',
        );
        $objective->save();

        $itemsBeforeSend = MailQueueItem::findUnsentByGroupAndMsgKey(
            $this->group->uuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
        );

        $sent = false;
        add_filter('collectme_email', function(Mailable $email) use (&$sent) {
            $template = new \ReflectionProperty(EmailObjectiveChange::class, 'template');
            self::assertInstanceOf(EmailTemplateObjectiveAdded::class, $template->getValue($email));
            $sent = true;
            return $email;
        });

        do_action('collectme_send_mails');

        $itemsAfterSend = MailQueueItem::findUnsentByGroupAndMsgKey(
            $this->group->uuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
        );

        self::assertCount(1, $itemsBeforeSend);
        self::assertCount(0, $itemsAfterSend);
        self::assertTrue($sent);
    }

    public function test_objectiveRaised(): void
    {
        $objective = new Objective(
            null,
            100,
            $this->group->uuid,
            'EmailActionTest',
        );
        $objective->save();

        $objective = new Objective(
            null,
            200,
            $this->group->uuid,
            'EmailActionTest',
        );
        $objective->save();

        $itemsBeforeSend = MailQueueItem::findUnsentByGroupAndMsgKey(
            $this->group->uuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
        );

        $sent = false;
        add_filter('collectme_email', function(Mailable $email) use (&$sent) {
            $template = new \ReflectionProperty(EmailObjectiveChange::class, 'template');
            self::assertInstanceOf(EmailTemplateObjectiveRaised::class, $template->getValue($email));
            $sent = true;
            return $email;
        });

        do_action('collectme_send_mails');

        $itemsAfterSend = MailQueueItem::findUnsentByGroupAndMsgKey(
            $this->group->uuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
        );

        self::assertCount(1, $itemsBeforeSend);
        self::assertCount(0, $itemsAfterSend);
        self::assertTrue($sent);
    }

    public function test_signatureAdded__objectiveAchievedAndRaised(): void
    {
        $objective = new Objective(
            null,
            100,
            $this->group->uuid,
            'EmailActionTest',
        );
        $objective->save();

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

        sleep(1);

        $objective = new Objective(
            null,
            200,
            $this->group->uuid,
            'EmailActionTest',
        );
        $objective->save();

        // we only want to test the objective change
        MailQueueItem::deleteUnsentByGroupAndMsgKey(
            $this->group->uuid,
            EnumMessageKey::COLLECTION_REMINDER,
        );

        $itemsBeforeSend = MailQueueItem::findUnsentByGroupAndMsgKey(
            $this->group->uuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
        );

        $sent = false;
        add_filter('collectme_email', function(Mailable $email) use (&$sent) {
            $template = new \ReflectionProperty(EmailObjectiveChange::class, 'template');
            self::assertInstanceOf(EmailTemplateObjectiveAchievedAndRaised::class, $template->getValue($email));
            $sent = true;
            return $email;
        });

        do_action('collectme_send_mails');

        $itemsAfterSend = MailQueueItem::findUnsentByGroupAndMsgKey(
            $this->group->uuid,
            EnumMessageKey::OBJECTIVE_CHANGE,
        );

        self::assertCount(1, $itemsBeforeSend);
        self::assertCount(0, $itemsAfterSend);
        self::assertTrue($sent);
    }
}