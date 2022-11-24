<?php

declare(strict_types=1);

namespace Collectme\Email;

interface EmailTemplate
{
    public function getBodyTemplate(): string;

    public function getSubjectTemplate(): string;
}