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

class MotdFormDelete extends BaseAction
{
    public function init()
    {
        $this->setPostContentType(self::POST_CONTENT_TYPE_JSON);
    }

    protected function checkInput()
    {
        $valid = $this->validateInput([
            'id'    => 'id',
            'ids'   => 'array_id'
        ]);

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

    public function doAction()
    {
        $output = [];
        $messages = [];

        if ($this->hasInput('id')) {
            $messages[] = ['id' => $this->getInput('id')];
        }

        foreach ($this->getInput('ids', []) as $id) {
            $messages[] = ['id' => $id];
        }

        try {
            if (!$messages) {
                throw new Exception(_('No message selected.'));
            }

            $this->module->storage->begin();
            $this->module->storage->delete($messages);
            $this->module->storage->commit();

            $output['success'] = [
                'title'     => _('Message deleted'),
                'messages'  => array_column(get_and_clear_messages(), 'message')
            ];
        }
        catch (Exception $e) {
            $output['error'] = [
                'title' => _('Cannot delete message'),
                'messages' => [$e->getMessage()]
            ];
        }

        $this->setResponse(new CControllerResponseData(['main_block' => json_encode($output)]));
    }
}
