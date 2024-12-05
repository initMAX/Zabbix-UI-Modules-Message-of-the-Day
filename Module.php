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


namespace Modules\Motd;

use API;
use APP;
use CMenuItem;
use CWebUser;
use Zabbix\Core\CModule;
use CController as Action;
use Modules\Motd\Actions\BaseAction;
use Modules\Motd\Service\Message as MessageService;

class Module extends CModule
{
    /** @var Service\FileStorage $storage */
    public $storage;

    public function getAssets(): array
    {
        $assets = parent::getAssets();

        if (strpos(($_GET['action']??''), 'module.motd.') !== 0) {
            // When not module routes are requested unregister non global assets.
            $assets = [
                'js' => ['motd.js'],
                'css' => ['motd.css']
            ];
        }

        return $assets;
    }

    public function init(): void
    {
        $this->boot();
        $this->registerMenuEntry();
    }

    public function onBeforeAction(Action $action): void
    {
        // Skip all notification actions.
        if (strpos($action->getAction(), 'notification.') === 0) {
            return;
        }

        if (is_a($action, BaseAction::class)) {
            $action->module = $this;
        }

        // Skip ajax actions.
        if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {
            return;
        }

        if (CWebUser::$data['userid']??0) {
            $service = new MessageService($this->storage);
            $user_groups = API::Usergroup()->get([
                'output' => ['usrgrpid'],
                'userids' => [CWebUser::$data['userid']]
            ]);
            $service->setGroupMessage(array_column($user_groups, 'usrgrpid'));
        }
    }

    protected function boot()
    {
        $config = $this->getConfig()['storage']??[];

        if ($config) {
            $class = __NAMESPACE__.'\\Service\\'.$config['class'];
            $this->storage = new $class($config);
            $this->storage->begin();

            return;
        }

        $config = $this->getManifest()['storage'];
        $class = __NAMESPACE__.'\\Service\\'.$config['class'];
        $this->storage = new $class($config);
        $config += $this->storage->setup();
        $this->setConfig(['storage' => $config]);
    }

    protected function registerMenuEntry()
    {
        if (CWebUser::getType() != USER_TYPE_SUPER_ADMIN) {
            return;
        }

        $menuItem = new CMenuItem(_('Message of the day'));
        $menuItem->setAction('module.motd.list');

        APP::Component()
            ->get('menu.main')
            ->find(_('Administration'))
            ->getSubMenu()
            ->add($menuItem);
    }
}
