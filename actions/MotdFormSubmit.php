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


namespace Modules\Motd\Actions;

use Exception;
use CControllerResponseData;
use Modules\Motd\Service\Message;

class MotdFormSubmit extends BaseAction
{
    public function init()
    {
        $this->setPostContentType(self::POST_CONTENT_TYPE_JSON);
    }

    protected function checkInput()
    {
        $this->module->storage->begin();
        $input = json_decode(file_get_contents('php://input'), true);
        $rules = [
            'id'                    => 'id',
            'status'                => 'in '.implode(',', [Message::STATUS_DISABLED, Message::STATUS_ENABLED]),
            'name'                  => 'required|not_empty|string',
            'active_since'          => 'required|abs_time',
            'active_till'           => 'required|abs_time',
            'message'               => 'required|not_empty|string'
        ];

        $valid = $this->validateInput($rules) && $this->validateTime();

        if (!$valid) {
            $this->setResponse(
                (new CControllerResponseData(['main_block' => json_encode([
                    'error' => [
                        'messages' => array_column(get_and_clear_messages(), 'message')
                    ]
                ])]))->disableView()
            );
        }

        return $valid;
    }

    protected function validateTime(): bool
    {
        $valid = true;
        $show_since = $this->hasInput('show_since') ? strtotime($this->getInput('show_since')) : 0;
        $active_since = strtotime($this->getInput('active_since'));
        $active_till = strtotime($this->getInput('active_till'));
        $max_ts = max($show_since, $active_since, $active_till);

        if ($active_since >= $active_till || $show_since > $active_since || $max_ts < time()) {
            $valid = false;
            error(_('Incorrect date and time for "Show since", "Active since" or "Active till" is defined.'));
        }

        if ($this->getInput('repeat_end', Message::REPEAT_END_NEVER) == Message::REPEAT_END_DATE) {
            $repeat_end_date = strtotime($this->getInput('repeat_end_date'));
            $active_till_ymd = strtotime(date('Y-m-d', $active_till));

            if ($repeat_end_date < $active_till_ymd) {
                $valid = false;
                error(_('Incorrect date for "Ends" is defined.'));
            }
        }

        $status = $this->getInput('status', Message::STATUS_DISABLED);

        if ($status === Message::STATUS_ENABLED && $valid && $active_since < $active_till) {
            $messages = $this->module->storage->get([
                'status' => Message::STATUS_ENABLED,
                'time_from' => $show_since > 0 ? $show_since : $active_since,
                'time_till' => $active_till
            ]);
            $messages = array_column($messages, null, 'id');
            unset($messages[$this->getInput('id', -1)]);

            if ($messages) {
                $valid = false;
                error(_s('Iterval intersects with already defined for: %1$s',
                    implode(', ', array_column($messages, 'name')))
                );
            }
        }

        return $valid;
    }

    public function doAction()
    {
        $output = [];
        $message = [
            'status' => Message::STATUS_DISABLED,
        ];

        $this->getInputs($message, Message::FIELDS);

        if ($this->getInput('repeat', Message::REPEAT_DISABLED) == Message::REPEAT_DISABLED) {
            unset($message['repeat_end_date'], $message['repeat_end_count']);
        }

        if ($this->getInput('repeat_end', Message::REPEAT_END_NEVER) != Message::REPEAT_END_DATE) {
            unset($message['repeat_end_date']);
        }

        if ($this->getInput('repeat_end', Message::REPEAT_END_NEVER) != Message::REPEAT_END_COUNT) {
            unset($message['repeat_end_count']);
        }

        foreach (['show_since', 'active_since', 'active_till', 'repeat_end_date'] as $time_field) {
            if ($this->hasInput($time_field)) {
                $message[$time_field] = strtotime($message[$time_field]);
            }
        }

        foreach (['repeat', 'repeat_value', 'repeat_interval', 'repeat_end_count'] as $int_field) {
            if ($this->hasInput($int_field)) {
                $message[$int_field] = intval($message[$int_field]);
            }
        }

        try {
            if ($this->hasInput('id')) {
                $this->module->storage->update([$message]);
                $this->module->storage->commit();
                $output['success']['title'] = _('Message updated');
            }
            else {
                $this->module->storage->create([$message]);
                $this->module->storage->commit();
                $output['success']['title'] = _('Message created');
            }

            $output['success']['messages'] = array_column(get_and_clear_messages(), 'message');
        }
        catch (Exception $e) {
            $output['error'] = [
                'title' => $this->hasInput('id')
                    ? _('Cannot update message')
                    : _('Cannot create message'),
                'messages' => [$e->getMessage()]
            ];
        }

        $this->setResponse(new CControllerResponseData(['main_block' => json_encode($output)]));
    }
}
