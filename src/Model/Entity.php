<?php

declare(strict_types=1);

namespace Collectme\Model;

use Collectme\Model\Database\DBField;
use Collectme\Model\Database\Persistable;
use Collectme\Model\Database\Persister;
use Collectme\Model\JsonApi\ApiConverter;
use Collectme\Model\JsonApi\ApiConvertible;
use Collectme\Model\JsonApi\ApiModelAttribute;
use Collectme\Model\JsonApi\ApiModelId;

abstract class Entity implements Persistable, ApiConvertible
{
    use ApiConverter;
    use Persister;

    #[ApiModelId]
    #[DBField]
    public ?string $uuid;

    #[ApiModelAttribute('created')]
    #[DBField('created_at')]
    #[DateProperty]
    public readonly ?\DateTime $created;

    #[ApiModelAttribute('updated')]
    #[DBField('updated_at')]
    #[DateProperty]
    public readonly ?\DateTime $updated;

    #[DBField('deleted_at')]
    #[DateProperty]
    public ?\DateTime $deleted;

    protected function __construct(
        ?string $uuid,
        null|\DateTime $created,
        null|\DateTime $updated,
        null|\DateTime $deleted
    ) {
        $this->uuid = $uuid;
        $this->created = $created;
        $this->updated = $updated;
        $this->deleted = $deleted;
    }

    /**
     * The updated field will be handled by a trigger in the database
     *
     * @return ?string
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function _convertDbUpdated(): ?string
    {
        return null;
    }

    /**
     * The created field will be handled by a trigger in the database
     *
     * @return ?string
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function _convertDbCreated(): ?string
    {
        return null;
    }
}