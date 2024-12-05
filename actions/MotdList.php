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

use API;
use CUrl;
use CArrayHelper;
use CProfile;
use CPagerHelper;
use CControllerResponseData;
use Modules\Motd\Service\Message;

class MotdList extends BaseAction
{
    protected function checkInput()
    {
        $this->validateInput([
            'filter_set'        => 'in 1',
            'filter_rst'        => 'in 1',
            'filter_groupids'   => 'array_id',
            'filter_name'       => 'string',
            'filter_state'      => 'in '.implode(',', [-1, Message::STATE_ACTIVE, Message::STATE_APPROACHING, Message::STATE_EXPIRED]),
            'filter_status'     => 'in '.implode(',', [-1, Message::STATUS_ENABLED, Message::STATUS_DISABLED]),
            'sort'              => 'in '.implode(',', ['name', 'show_since', 'active_since', 'active_till']),
            'sortorder'         => 'in '.implode(',', [ZBX_SORT_UP, ZBX_SORT_DOWN]),
            'page'              => 'ge 1'
        ]);

        return true;
    }

    protected function doAction()
    {
        if ($this->hasInput('filter_set')) {
            $this->updateProfiles();
        }
        elseif ($this->hasInput('filter_rst')) {
            $this->deleteProfiles();
        }

        $filter = $this->getFilter();
        $messages = $this->module->storage->get($filter);
        $data = [
            'sort' => $this->getInput('sort', 'name'),
            'sortorder' => $this->getInput('sortorder', ZBX_SORT_UP)
        ];
        CArrayHelper::sort($messages, [['field' => $data['sort'], 'order' => $data['sortorder']]]);
        $paging = CPagerHelper::paginate($this->getInput('page', 1), $messages, $data['sortorder'],
            (new CUrl('zabbix.php'))->setArgument('action', $this->getAction())
        );
        $data += [
            'filter' => $filter,
            'paging' => $paging,
            'messages' => $messages,
            'csrf_token' => [
                'motd.form.enable' => $this->getActionCsrfToken('motd.form.enable'),
                'motd.form.disable' => $this->getActionCsrfToken('motd.form.disable'),
                'motd.form.delete' => $this->getActionCsrfToken('motd.form.delete')
            ],
        ];

        $response = new CControllerResponseData($data);
        $response->setTitle(_('Message of the Day'));
        $this->setResponse($response);
    }

    protected function updateProfiles()
    {
        CProfile::updateArray('module.motd.filter_groupids', $this->getInput('filter_groupids', []), PROFILE_TYPE_ID);
        CProfile::update('module.motd.filter_name', $this->getInput('filter_name', ''), PROFILE_TYPE_STR);
        CProfile::update('module.motd.filter_state', $this->getInput('filter_state', -1), PROFILE_TYPE_INT);
        CProfile::update('module.motd.filter_status', $this->getInput('filter_status', -1), PROFILE_TYPE_INT);
    }

    protected function deleteProfiles()
    {
        CProfile::deleteIdx('module.motd.filter_groupids');
        CProfile::deleteIdx('module.motd.filter_name');
        CProfile::deleteIdx('module.motd.filter_state');
        CProfile::deleteIdx('module.motd.filter_status');
    }

    protected function getFilter(): array
    {
        $filter = [
            'groupids'          => CProfile::getArray('module.motd.filter_groupids', []),
            'name'              => CProfile::get('module.motd.filter_name', ''),
            'state'             => CProfile::get('module.motd.filter_state', -1),
            'status'            => CProfile::get('module.motd.filter_status', -1),
            'filter_profile'    => 'module.motd.filter',
            'filter_tab'        => CProfile::get('module.motd.filter.active', 1)
        ];

        if ($filter['groupids']) {
            $groups = API::HostGroup()->get([
                'output' => ['name', 'groupid'],
                'groupids' => $filter['groupids']
            ]);
            $groups = CArrayHelper::renameObjectsKeys($groups, ['groupid' => 'id']);
            $filter['filter_groupids_multiselect'] = $groups;
        }

        return $filter;
    }
}
