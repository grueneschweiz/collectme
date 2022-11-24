<?php

declare(strict_types=1);

namespace Unit\Misc;

use Collectme\Email\EmailCollectionReminder;
use Collectme\Email\QueueableEmailFactory;
use Collectme\Misc\Mailer;
use Collectme\Misc\MailQueueItemProcessor;
use Collectme\Misc\Settings;
use Collectme\Model\Entities\Cause;
use Collectme\Model\Entities\EnumGroupType;
use Collectme\Model\Entities\EnumLang;
use Collectme\Model\Entities\EnumMailQueueItemTrigger;
use Collectme\Model\Entities\EnumMessageKey;
use Collectme\Model\Entities\EnumPermission;
use Collectme\Model\Entities\Group;
use Collectme\Model\Entities\MailQueueItem;
use Collectme\Model\Entities\Role;
use Collectme\Model\Entities\User;
use PHPUnit\Framework\TestCase;

use const Collectme\CAUSE_MINIMAL_DATA_RETENTION_DURATION;

class MailQueueItemProcessorTest extends TestCase
{

    public function test_process__causeDeleted(): void
    {
        $item = $this->getMailQueueItem();
        $cause = Cause::get($item->group()->causeUuid);
        $cause->delete();

        $emailFactory = $this->createMock(QueueableEmailFactory::class);
        $emailFactory->expects($this->never())->method('get');

        $processor = $this->makeMailQueueItemProcessor(null, $emailFactory);

        $processor->process($item);
        self::assertNotNull($item->deleted);
    }

    private function getMailQueueItem(): MailQueueItem
    {
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
            'getMailQueueItem'
        );
        $user->save();

        $group = new Group(
            null,
            'test_' . wp_generate_password(),
            EnumGroupType::PERSON,
            $cause->uuid,
            false,
        );
        $group->save();

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
            EnumMailQueueItemTrigger::GROUP,
            date_create('-1 day'),
        );
        return $mailQueueItem->save();
    }

    private function makeMailQueueItemProcessor(
        ?Mailer $mailer = null,
        ?QueueableEmailFactory $emailFactory = null
    ): MailQueueItemProcessor {
        if (!$mailer) {
            $mailer = $this->createMock(Mailer::class);
        }
        if (!$emailFactory) {
            $emailFactory = $this->createMock(QueueableEmailFactory::class);
        }

        return new MailQueueItemProcessor($mailer, $emailFactory);
    }

    public function test_process__causeDataRetentionExpired(): void
    {
        $item = $this->getMailQueueItem();
        $causeUuid = $item->group()->causeUuid;

        $stop = date_create('-1 second')
            ->sub(new \DateInterval(CAUSE_MINIMAL_DATA_RETENTION_DURATION));

        Settings::getInstance()->setTimings([
            'start' => null,
            'stop' => $stop,
        ], $causeUuid);

        $emailFactory = $this->createMock(QueueableEmailFactory::class);
        $emailFactory->expects($this->never())->method('get');

        $processor = $this->makeMailQueueItemProcessor(null, $emailFactory);

        $processor->process($item);
        self::assertNotNull($item->deleted);
    }

    public function test_process__causeNotActive(): void
    {
        $item = $this->getMailQueueItem();
        $causeUuid = $item->group()->causeUuid;

        Settings::getInstance()->setTimings([
            'start' => null,
            'stop' => date_create('-1 second'),
        ], $causeUuid);

        Settings::getInstance()->setMailDelays([
            EnumMessageKey::COLLECTION_REMINDER->value => new \DateInterval('PT0S'),
            EnumMessageKey::OBJECTIVE_CHANGE->value => new \DateInterval('PT0S'),
        ], $causeUuid);

        $emailFactory = $this->createMock(QueueableEmailFactory::class);
        $emailFactory->expects($this->never())->method('get');

        $processor = $this->makeMailQueueItemProcessor(null, $emailFactory);

        $processor->process($item);
        self::assertNull($item->deleted);
    }

    public function test_process__itemNotEnabled(): void
    {
        $item = $this->getMailQueueItem();

        $emailFactory = $this->createMock(QueueableEmailFactory::class);
        $emailFactory->expects($this->never())->method('get');

        $processor = $this->makeMailQueueItemProcessor(null, $emailFactory);

        $processor->process($item);
        self::assertNull($item->deleted);
    }

    public function test_process__itemNotDueForSending(): void
    {
        $item = $this->getMailQueueItem();
        $causeUuid = $item->group()->causeUuid;

        Settings::getInstance()->setMailDelays([
            EnumMessageKey::COLLECTION_REMINDER->value => new \DateInterval('P2D'),
            EnumMessageKey::OBJECTIVE_CHANGE->value => new \DateInterval('P2D'),
        ], $causeUuid);

        $emailFactory = $this->createMock(QueueableEmailFactory::class);
        $emailFactory->expects($this->never())->method('get');

        $processor = $this->makeMailQueueItemProcessor(null, $emailFactory);

        $processor->process($item);
        self::assertNull($item->deleted);
    }

    public function test_process__emailShouldNotBeSent(): void
    {
        $item = $this->getMailQueueItem();
        $item->messageKey = EnumMessageKey::COLLECTION_REMINDER;
        $item->save();

        $causeUuid = $item->group()->causeUuid;

        Settings::getInstance()->setMailDelays([
            EnumMessageKey::COLLECTION_REMINDER->value => new \DateInterval('PT0S'),
            EnumMessageKey::OBJECTIVE_CHANGE->value => new \DateInterval('P1Y'),
        ], $causeUuid);

        $email = $this->createMock(EmailCollectionReminder::class);
        $email->expects($this->once())
            ->method('shouldBeSent')
            ->willReturn(false);

        $email->expects($this->never())
            ->method('afterSent');

        $emailFactory = $this->createMock(QueueableEmailFactory::class);
        $emailFactory->expects($this->once())
            ->method('get')
            ->with(EnumMessageKey::COLLECTION_REMINDER)
            ->willReturn($email);

        $mailer = $this->createMock(Mailer::class);
        $mailer->expects($this->never())
            ->method('send');

        $processor = $this->makeMailQueueItemProcessor($mailer, $emailFactory);

        $processor->process($item);
        self::assertNull($item->deleted);
    }

    public function test_process__userNoMailPermission(): void
    {
        $item = $this->getMailQueueItem();
        $item->messageKey = EnumMessageKey::COLLECTION_REMINDER;
        $item->save();

        $users = User::findWithWritePermissionForGroup($item->groupUuid);
        array_map(static function(User $user) {
            $user->mailPermission = false;
            $user->save();
        }, $users);

        $causeUuid = $item->group()->causeUuid;

        Settings::getInstance()->setMailDelays([
            EnumMessageKey::COLLECTION_REMINDER->value => new \DateInterval('PT0S'),
            EnumMessageKey::OBJECTIVE_CHANGE->value => new \DateInterval('P1Y'),
        ], $causeUuid);

        $email = $this->createMock(EmailCollectionReminder::class);
        $email->expects($this->once())
            ->method('shouldBeSent')
            ->willReturn(true);

        $email->expects($this->once())
            ->method('afterSent');

        $emailFactory = $this->createMock(QueueableEmailFactory::class);
        $emailFactory->expects($this->once())
            ->method('get')
            ->with(EnumMessageKey::COLLECTION_REMINDER)
            ->willReturn($email);

        $mailer = $this->createMock(Mailer::class);
        $mailer->expects($this->never())
            ->method('send');

        $processor = $this->makeMailQueueItemProcessor($mailer, $emailFactory);

        $processor->process($item);
        self::assertNotNull($item->sent);
    }

    public function test_process__send(): void
    {
        $item = $this->getMailQueueItem();
        $item->messageKey = EnumMessageKey::COLLECTION_REMINDER;
        $item->save();

        $causeUuid = $item->group()->causeUuid;

        Settings::getInstance()->setMailDelays([
            EnumMessageKey::COLLECTION_REMINDER->value => new \DateInterval('PT0S'),
            EnumMessageKey::OBJECTIVE_CHANGE->value => new \DateInterval('P1Y'),
        ], $causeUuid);

        $email = $this->createMock(EmailCollectionReminder::class);
        $email->expects($this->once())
            ->method('shouldBeSent')
            ->willReturn(true);

        $users = User::findWithWritePermissionForGroup($item->groupUuid);
        $email->expects($this->once())
            ->method('setUser')
            ->with($this->equalTo($users[0]));

        $email->expects($this->once())
            ->method('afterSent');

        $emailFactory = $this->createMock(QueueableEmailFactory::class);
        $emailFactory->expects($this->once())
            ->method('get')
            ->with(EnumMessageKey::COLLECTION_REMINDER)
            ->willReturn($email);

        $mailer = $this->createMock(Mailer::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($email, $causeUuid);

        $processor = $this->makeMailQueueItemProcessor($mailer, $emailFactory);

        $initialLocale = get_locale();

        $localeWasSwitched = false;
        add_action(
            'switch_locale',
            function() use (&$localeWasSwitched) {
                $localeWasSwitched = true;
            }
        );

        $processor->process($item);

        self::assertNotNull($item->sent);
        self::assertEquals($initialLocale, get_locale());
        self::assertTrue($localeWasSwitched);
    }
}
