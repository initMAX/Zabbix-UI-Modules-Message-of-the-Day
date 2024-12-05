<?php declare(strict_types = 0);
/*
** Copyright (C) 2001-2024 initMAX s.r.o.
**
** This program is free software: you can redistribute it and/or modify it under the terms of
** the GNU Affero General Public License as published by the Free Software Foundation, version 3.
**
** This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
** without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
** See the GNU Affero General Public License for more details.
**
** You should have received a copy of the GNU Affero General Public License along with this program.
** If not, see <https://www.gnu.org/licenses/>.
**/


namespace Modules\Motd\Service;

class Message
{
    const STATE_ACTIVE = 0;
    const STATE_APPROACHING = 1;
    const STATE_EXPIRED = 2;

    const REPEAT_DISABLED = 0;
    const REPEAT_ENABLED = 1;

    const REPEAT_DAY = 0;
    const REPEAT_WEEK = 1;
    const REPEAT_MONTH = 2;
    const REPEAT_YEAR = 3;

    const REPEAT_END_NEVER = 0;
    const REPEAT_END_DATE = 1;
    const REPEAT_END_COUNT = 2;

    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    const FIELDS = [
        'id',
        'status',
        'name',
        'usrgrpids',
        'active_since',
        'active_till',
        'message',
    ];

    /** @var FileStorage $storage */
    protected $storage;

    public function __construct(StorageAbstract $storage)
    {
        $this->storage = $storage;
    }

    public function setGroupMessage(array $usrgrpids): void
    {
        $this->storage->begin();

        $messages = $this->storage->get([
            'status' => Message::STATUS_ENABLED,
            'state' => Message::STATE_ACTIVE,
            'sort' => [['field' => 'active_since', 'order' => ZBX_SORT_UP]]
        ]);

        if (!$messages) {
            setcookie('motd_message', '', 0);

            return;
        }

        $message = reset($messages);
        $data = [
            'message' => $message['message'],
            'active_since' => date('Y-m-d H:i', $message['active_since']),
            'active_till' => date('Y-m-d H:i', $message['active_till']),
            'color' => '1f65f4'
        ];

        setcookie('motd_message', base64_encode(json_encode($data)), 0);
    }
}
