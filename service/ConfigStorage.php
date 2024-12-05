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

use APP;
use Exception;
use ModuleManager;

class ConfigStorage extends FileStorage
{
    protected array $messages = [];
    protected bool $dirty = false;

    public function __construct(array $config)
    {
        $this->messages = $config['data']??[];
    }

    public function begin()
    {
        if (!is_array($this->messages)) {
            throw new Exception('Failed to initialize file storage.');
        }
    }

    public function commit()
    {
        if ($this->dirty !== true) {
            return;
        }

        $state = false;
        /** @var \ModuleManager $manager */
        $manager = APP::ModuleManager();
        /** @var \Modules\Motd\Module $module */
        $module = $manager->getActionModule();

        if ($module) {
            $config = $module->getConfig();
            $config['storage']['data'] = $this->messages;
            $module->setConfig($config);
            $state = true;
        }

        if (!$state) {
            throw new Exception('Failed to commit messages storage.');
        }

        $this->dirty = false;
    }

    public function setup(): array
    {
        return [];
    }
}
