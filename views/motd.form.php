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


use Modules\Motd\Service\Message;

/**
 * @var CView $this
 * @var array $data
 */

$form = (new CFormGrid())->addClass('motd-config-form');
$action = 'document.dispatchEvent(new CustomEvent("%1$s", {detail:{button: this, overlay: arguments[0]}}))';
$js_data = [];
$switcher = [];

if ($data['id']) {
    $form->addItem(new CVar('id', $data['id']));

    $buttons = [
        [
            'title' => _('Update'),
            'class' => '',
            'keepOpen' => true,
            'isSubmit' => true,
            'action' => sprintf($action, 'motd.form.submit'),
            'mvc_action' => 'motd.form.submit'
        ],
        [
            'title' => _('Delete'),
            'confirmation' => _('Delete selected message?'),
            'class' => 'btn-alt',
            'keepOpen' => true,
            'isSubmit' => false,
            'action' => sprintf($action, 'motd.form.delete'),
            'mvc_action' => 'motd.form.delete'
        ]
    ];
}
else {
    $buttons = [
        [
            'title' => _('Add'),
            'class' => '',
            'keepOpen' => true,
            'isSubmit' => true,
            'action' => sprintf($action, 'motd.form.submit'),
            'mvc_action' => 'motd.form.submit'
        ]
    ];
}

$form->addItem([
    new CSpan(''),
    (new CImg($this->getAssetsPath() . '/img/initmax-free.svg'))
        ->addClass('initmax-logo'),
]);

$form->addItem([
    new CLabel(_('Enabled'), 'status'),
    new CFormField((new CCheckBox('status', Message::STATUS_ENABLED))
        ->setChecked($data['status'] == Message::STATUS_ENABLED)
    )
]);

$form->addItem([
    (new CLabel(_('Name'), 'name'))->setAsteriskMark(),
    new CFormField((new CTextBox('name', $data['name']))
        ->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
        ->setAriaRequired()
        ->setAttribute('autofocus', 'autofocus')
)]);

$form->addItem([
    new CLabel(_('Show since'), 'show_since'),
    new CFormField([
        (new CDateSelector('show_since', $data['show_since'] === '' ? '' : date(ZBX_DATE_TIME, $data['show_since'])))
            ->setDateFormat(ZBX_DATE_TIME)
            ->setPlaceholder(ZBX_DATE_TIME)
            ->addClass(ZBX_STYLE_FORM_INPUT_MARGIN)
            ->setEnabled(false),
        (new CColor('show_since_color', $data['show_since_color']))
            ->setEnabled(false),
    ])
]);

$form->addItem([
    (new CLabel(_('Active since'), 'active_since'))->setAsteriskMark(),
    new CFormField([
        (new CDateSelector('active_since', date(ZBX_DATE_TIME, $data['active_since'])))
            ->setDateFormat(ZBX_DATE_TIME)
            ->setPlaceholder(ZBX_DATE_TIME)
            ->setAriaRequired()
            ->addClass(ZBX_STYLE_FORM_INPUT_MARGIN),
        (new CColor('active_since_color', $data['active_since_color']))
            ->setEnabled(false),
    ])
]);

$form->addItem([
    (new CLabel(_('Active till'), 'active_till'))->setAsteriskMark(),
    new CFormField(
        (new CDateSelector('active_till', date(ZBX_DATE_TIME, $data['active_till'])))
            ->setDateFormat(ZBX_DATE_TIME)
            ->setPlaceholder(ZBX_DATE_TIME)
            ->setAriaRequired()
    )
]);

$switcher['repeat'] = [Message::REPEAT_DISABLED => [], Message::REPEAT_ENABLED => []];
$form->addItem([
    new CLabel(_('Repeat'), 'repeat'),
    new CFormField((new CRadioButtonList('repeat', (int) $data['repeat']))
        ->addValue(_('No'), Message::REPEAT_DISABLED)
        ->addValue(_('Yes'), Message::REPEAT_ENABLED)
        ->setEnabled(false)
        ->setModern()
    )
]);

$form->addItem([
    (new CLabel(_('Message')))->setAsteriskMark(),
    new CFormField(
        (new CTextArea('message', $data['message'], ['rows' => 3]))
            ->setWidth(ZBX_TEXTAREA_STANDARD_WIDTH)
    )
]);

$form->addItem([
    new CFormField((new CDiv(
            sprintf(_('Server time: %1$s'), date(ZBX_DATE_TIME))
        ))->addClass(ZBX_STYLE_GREY)->addStyle('padding-bottom: 2rem;')
    )
]);

$form->addItem([
    (new CSpan(_('PRO version')))->addClass(ZBX_STYLE_RIGHT)->addStyle('font-weight: bold;'),
    (new CDiv([
        new CSpan(_('➕ Unlimited number of messages created in module')),
        new CTag('br'),
        new CSpan(_('➕ Show since feature')),
        new CTag('br'),
        new CSpan(_('➕ Repeat feature')),
        new CTag('br'),
        new CSpan(_('➕ Message bar color settings')),
        new CTag('br'),
        new CSpan(_('➕ Removing initMAX logo from message bar')),
    ]))->addStyle('color: #7150f7;')
]);

$form->addItem([
    new CSpan(''),
    (new CDiv([(new CSpan('Get PRO: '))->addStyle('font-weight: bold;'), new CLink('info@initmax.com', 'mailto:info@initmax.com?subject=Inquiry%20Message%20of%20the%20Day%20PRO%20version')]))->addStyle('padding-top: 2rem;'),
]);


$js_data['switcher'] = $switcher;
$js_data['csrf_token'] = $data['csrf_token'];
$output = [
    'header' => $data['id'] ? _('New message of the day') : _('Message of the day'),
    'body' => (string) (new CForm('post', '?action='.$data['submit_action']))
        ->setName('motd_message_form')
        ->addItem($form),
    'script_inline' => 'document.dispatchEvent(new CustomEvent("motd.form.init",'.json_encode(['detail' => $js_data]).'))',
    'buttons' => $buttons
];

if ($data['user']['debug_mode'] == GROUP_DEBUG_MODE_ENABLED) {
    CProfiler::getInstance()->stop();
    $output['debug'] = CProfiler::getInstance()->make()->toString();
}

echo json_encode($output);