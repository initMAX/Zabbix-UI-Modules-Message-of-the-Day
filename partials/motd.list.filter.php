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


/**
 * @var CPartial $this
 */

use Modules\Motd\Service\Message;

(new CFilter())
    ->addVar('action', 'module.motd.list')
    ->setResetUrl((new CUrl('zabbix.php'))->setArgument('action', 'module.motd.list'))
    ->setProfile('module.motd.filter')
    ->setActiveTab($data['filter_tab'])
    ->addFilterTab(_('Filter'), [
        (new CFormGrid())
            ->addClass(CFormGrid::ZBX_STYLE_FORM_GRID_LABEL_WIDTH_TRUE)
            ->addItem([
                new CLabel(_('Name'), 'filter_name'),
                new CFormField(
                    (new CTextBox('filter_name', $data['name']??''))
                        ->setWidth(ZBX_TEXTAREA_FILTER_STANDARD_WIDTH)
                        ->setAttribute('autofocus', 'autofocus')
                )
            ]),
        (new CFormGrid())
            ->addClass(CFormGrid::ZBX_STYLE_FORM_GRID_LABEL_WIDTH_TRUE)
            ->addItem([
                new CLabel(_('State')),
                new CFormField(
                    (new CRadioButtonList('filter_state', intval($data['state']??-1)))
                        ->addValue(_('Any'), -1)
                        ->addValue(_('Active'), Message::STATE_ACTIVE)
                        ->addValue(_('Approaching'), Message::STATE_APPROACHING)
                        ->addValue(_('Expired'), Message::STATE_EXPIRED)
                        ->setModern(true)
                )
            ]),
        (new CFormGrid())
            ->addClass(CFormGrid::ZBX_STYLE_FORM_GRID_LABEL_WIDTH_TRUE)
            ->addItem([
                new CLabel(_('Status')),
                new CFormField(
                    (new CRadioButtonList('filter_status', intval($data['status']??-1)))
                        ->addValue(_('All'), -1)
                        ->addValue(_('Enabled'), Message::STATUS_ENABLED)
                        ->addValue(_('Disabled'), Message::STATUS_DISABLED)
                        ->setModern(true)
                )
            ]),
    ])->show();
