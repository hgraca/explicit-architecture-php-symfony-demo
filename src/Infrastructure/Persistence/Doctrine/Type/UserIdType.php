<?php

declare(strict_types=1);

/*
 * This file is part of the Explicit Architecture POC,
 * which is created on top of the Symfony Demo application.
 *
 * (c) Herberto Graça <herberto.graca@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Acme\App\Infrastructure\Persistence\Doctrine\Type;

use Acme\App\Core\Component\User\Domain\User\UserId;

final class UserIdType extends AbstractUuidType
{
    protected function getMappedClass(): string
    {
        return UserId::class;
    }
}
