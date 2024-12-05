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
 * @var CView $this
 * @var array $data
 */

use Modules\Motd\Service\Message;

$this->addJsFile('colorpicker.js');
$this->addJsFile('class.calendar.js');

$view_url = (new CUrl('zabbix.php'))
    ->setArgument('action', 'module.motd.list')
    ->getUrl();

$form = (new CForm())
    ->removeId()
    ->setName('js-motd-list');

$table = (new CTableInfo())
    ->setHeader([
        (new CColHeader(
            (new CCheckBox('all_motd'))->onClick("checkAll('".$form->getName()."', 'all_motd', 'ids');")
        ))->addClass(ZBX_STYLE_CELL_WIDTH),
        make_sorting_header(_('Name'), 'name', $data['sort'], $data['sortorder'], $view_url),
        make_sorting_header(_('Active since'), 'active_since', $data['sort'], $data['sortorder'], $view_url),
        make_sorting_header(_('Active till'), 'active_till', $data['sort'], $data['sortorder'], $view_url),
        _('Status'),
        _('Message')
    ]);

$state_label = [
    Message::STATE_ACTIVE => [ZBX_STYLE_COLOR_POSITIVE, _('Active')],
    Message::STATE_APPROACHING => [ZBX_STYLE_COLOR_WARNING, _('Approaching')],
    Message::STATE_EXPIRED => [ZBX_STYLE_COLOR_NEGATIVE, _('Expired')]
];

$event = 'document.dispatchEvent(new CustomEvent("motd.form.edit", {detail:{id:%1$d}}))';

foreach ($data['messages'] as $message) {
    $state = $message['status'] == Message::STATUS_ENABLED
        ? ($state_label[$message['state']]??[ZBX_STYLE_GREY, _('Unknown')])
        : [ZBX_STYLE_GREY, _('Disabled')];
    $repeat = _('No');

    $table->addRow([
        new CCheckBox('ids['.$message['id'].']', $message['id']),
        (new CLink($message['name'], '#'))->onClick(sprintf($event, $message['id'])),
        date(ZBX_DATE_TIME, $message['active_since']),
        date(ZBX_DATE_TIME, $message['active_till']),
        (new CDiv($state[1]))->addClass($state[0]),
        $message['message']
    ]);
}


$form->addItem([$table, $data['paging']]);

$form->addItem(
    new CActionButtonList('action', 'ids', [
        'enable' => [
            'name' => _('Enable'),
            'attributes' => [
                'class' => ZBX_STYLE_BTN_ALT,
                'formaction' => 'motd.form.enable'
            ],
            'csrf_token' => $data['csrf_token']['motd.form.enable']
        ],
        'disable' => [
            'name' => _('Disable'),
            'attributes' => [
                'class' => ZBX_STYLE_BTN_ALT,
                'formaction' => 'motd.form.disable'
            ],
            'csrf_token' => $data['csrf_token']['motd.form.disable']
        ],
        'delete' => [
            'name' => _('Delete'),
            'attributes' => [
                'class' => ZBX_STYLE_BTN_ALT,
                'formaction' => 'motd.form.delete',
            ],
            'csrf_token' => $data['csrf_token']['motd.form.delete'],
            'confirm_singular' => _('Delete selected message?'),
            'confirm_plural' => _('Delete selected messages?')
        ]
    ], 'motd')
);

(new CHtmlPage())
    ->addItem((new CDiv())->setId('motd-list-page'))
    ->setTitle(_('Message of the Day'))
    ->setControls(
        (new CTag('nav', true,
            (new CList())
                ->addItem(
                    (new CSubmitButton(_('Create message of the day')))
                        ->setEnabled($data['messages']?false:true)
                )
                ->onClick($data['messages']?'':'document.dispatchEvent(new CustomEvent("motd.form.edit", {detail:{}}))')
        ))->setAttribute('aria-label', _('Content controls'))
    )
    ->addItem(new CPartial('motd.list.filter', $data['filter'] + [
        'active_tab' => 1,
        'groups_multiselect' => []
    ]))
    ->addItem($form)
    ->show();
