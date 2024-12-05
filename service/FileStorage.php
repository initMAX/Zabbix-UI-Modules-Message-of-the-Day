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

use Exception;
use CArrayHelper;

class FileStorage extends StorageAbstract
{
    protected ?string $path = null;
    protected array $messages = [];
    protected bool $dirty = false;

    public function __construct(array $config)
    {
        $this->path = $config['path']??null;
    }

    public function get(array $options): array
    {
        $ts = time();
        $messages = $this->messages;

        if (is_array($options['ids']??'')) {
            $messages = array_intersect_key($messages, array_flip((array) $options['ids']));
        }

        if (is_array($options['usrgrpids']??'')) {
            foreach ($messages as $i => $message) {
                if (!array_intersect($message['usrgrpids'], (array) $options['usrgrpids'])) {
                    unset($messages[$i]);
                }
            }
        }

        if ($options['name']??'' !== '') {
            foreach ($messages as $i => $message) {
                if (stripos($message['name'], $options['name']) === false) {
                    unset($messages[$i]);
                }
            }
        }
        
        if (($options['status']??-1) != -1) {
            foreach ($messages as $i => $message) {
                if (($message['status']??Message::STATUS_ENABLED) != $options['status']) {
                    unset($messages[$i]);
                }
            }
        }

        $time_from = $options['time_from']??null;
        $time_till = $options['time_till']??null;

        foreach ($messages as $i => &$message) {
            $show_ts = $message['active_since'];
            $end_ts = $message['active_till'];

            if ($time_from !== null && $time_till !== null && !($time_from < $end_ts && $time_till > $show_ts)) {
                unset($messages[$i]);

                continue;
            }

            if ($end_ts >= $ts && $show_ts <= $ts) {
                $message['state'] = Message::STATE_ACTIVE;
            }
            else if ($end_ts < $ts) {
                $message['state'] = Message::STATE_EXPIRED;
            }
            else {
                $message['state'] = Message::STATE_APPROACHING;
            }
        }
        unset($message);

        if (is_numeric($options['state']??null) && $options['state'] != -1) {
            foreach ($messages as $i => $message) {
                if ($options['state'] != $message['state']) {
                    unset($messages[$i]);
                }
            }
        }

        if ($options['sort']??false) {
            CArrayHelper::sort($messages, $options['sort']);
        }

        return $messages;
    }

    public function create(array $messages): array
    {
        $id = $this->messages ? max(array_keys($this->messages)) : 0;
        $id++;
        $fields = array_flip(Message::FIELDS);

        foreach ($messages as &$message) {
            $message['id'] = $id;
            $this->messages[$id] = array_intersect_key($message, $fields);

            $id++;
        }
        unset($message);

        $this->dirty = true;

        return $messages;
    }

    public function update(array $messages): array
    {
        $fields = array_flip(Message::FIELDS);

        foreach ($messages as $message) {
            $message = array_intersect_key($message, $fields);
            $this->messages[$message['id']] = array_merge($this->messages[$message['id']], $message);
        }

        $this->dirty = true;

        return $messages;
    }

    public function delete(array $messages): array
    {
        $this->messages = array_diff_key($this->messages, array_flip(array_column($messages, 'id')));

        $this->dirty = true;

        return $messages;
    }

    public function begin()
    {
        if ($this->path === null || !file_exists($this->path)) {
            throw new Exception('Failed to initialize file storage.');
        }

        $this->messages = json_decode(file_get_contents($this->path), true);

        if (!is_array($this->messages)) {
            throw new Exception('Failed to initialize file storage.');
        }
    }

    public function commit()
    {
        if ($this->dirty !== true) {
            return;
        }

        $state = file_put_contents($this->path, json_encode($this->messages));

        if (!$state) {
            throw new Exception('Failed to commit messages storage.');
        }

        $this->dirty = false;
    }

    public function setup(): array
    {
        $this->path = $this->path??realpath(__DIR__.'/../var').sprintf('/storage-%1$s.json', base_convert(time(), 10, 36));
        $this->dirty = true;
        $this->commit();

        return ['path' => $this->path];
    }
}
