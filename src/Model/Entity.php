<?php

declare(strict_types=1);

namespace Collectme\Model;

use Collectme\Model\Database\DBField;
use Collectme\Model\Database\DBFieldDate;
use Collectme\Model\Database\Persistable;
use Collectme\Model\Database\Persister;
use Collectme\Model\JsonApi\ApiModelAttribute;
use Collectme\Model\JsonApi\ApiModelId;
use Collectme\Model\JsonApi\ApiSerializeable;
use Collectme\Model\JsonApi\ApiSerializer;

abstract class Entity implements Persistable, ApiSerializeable
{
    use ApiSerializer;
    use Persister;

    #[ApiModelId]
    #[DBField]
    public ?string $uuid;

    #[ApiModelAttribute('created')]
    #[DBField('created_at')]
    #[DBFieldDate]
    public readonly ?\DateTime $created;

    #[ApiModelAttribute('updated')]
    #[DBField('updated_at')]
    #[DBFieldDate]
    public readonly ?\DateTime $updated;

    #[DBField('deleted_at')]
    #[DBFieldDate]
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