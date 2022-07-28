<?php

declare(strict_types=1);

namespace Collectme\Model\Entities;

use Collectme\Exceptions\CollectmeDBException;
use Collectme\Misc\Settings;
use Collectme\Model\DateProperty;
use Collectme\Model\JsonApi\ApiConverter;
use Collectme\Model\JsonApi\ApiConvertible;
use Collectme\Model\JsonApi\ApiModelAttribute;
use Collectme\Model\JsonApi\ApiModelId;
use Collectme\Model\JsonApi\ApiModelRelationship;
use Collectme\Model\JsonApi\ApiModelType;

#[ApiModelType('stat')]
class Stat implements ApiConvertible
{
    use ApiConverter;

    private const ID = 'overview';

    #[ApiModelId]
    private string $id;

    public function __construct(
        #[ApiModelAttribute]
        public float $pledged,

        #[ApiModelAttribute]
        public float $registered,

        #[ApiModelRelationship(Cause::class)]
        public string $causeUuid,

        #[ApiModelAttribute('updated')]
        #[DateProperty]
        public readonly \DateTime $updated,
    ) {
        $this->id = self::makeId($this->causeUuid);
    }

    private static function makeId(string $causeUuid): string
    {
        return self::ID . '-' . $causeUuid;
    }

    /**
     * @throws CollectmeDBException
     */
    public static function getByCause(string $causeUuid): self
    {
        $cached = self::getCached($causeUuid);

        if (!$cached) {
            $cached = self::updateCache($causeUuid);
        }

        return $cached;
    }

    private static function getCached(string $causeUuid): ?self
    {
        $stat = get_transient(self::getCacheKey($causeUuid));

        if ($stat === false) {
            return null;
        }

        $stat['updated'] = date_create($stat['updated']);

        return new self(...$stat);
    }

    private static function getCacheKey(string $causeUuid): string
    {
        return "collectme_" . self::makeId($causeUuid);
    }

    /**
     * @throws CollectmeDBException
     */
    public static function updateCache(string $causeUuid): self
    {
        $pledged = Objective::totalByCauseAndType($causeUuid, EnumGroupType::PERSON);
        $registered = SignatureEntry::totalByCauseAndType($causeUuid, EnumGroupType::PERSON);

        $pledgeSettings = Settings::getInstance()->getPledgeSettings($causeUuid);
        $signatureSettings = Settings::getInstance()->getSignatureSettings($causeUuid);

        $pledgeObjective = $pledgeSettings['objective'];
        $signatureObjective = $signatureSettings['objective'];

        $pledgeOffset = $pledgeSettings['offset'];
        $signatureOffset = $signatureSettings['offset'];

        $stat = new self(
            ($pledged + $pledgeOffset) / $pledgeObjective,
            ($registered + $signatureOffset) / $signatureObjective,
            $causeUuid,
            date_create(),
        );

        $stat->cache();

        return $stat;
    }

    private function cache(): void
    {
        set_transient(
            self::getCacheKey($this->causeUuid),
            [
                'pledged' => $this->pledged,
                'registered' => $this->registered,
                'causeUuid' => $this->causeUuid,
                'updated' => $this->updated->format(DATE_RFC3339_EXTENDED),
            ],
            DAY_IN_SECONDS
        );
    }

    public static function clearCache(string $causeUuid): void
    {
        delete_transient(self::getCacheKey($causeUuid));
    }
}