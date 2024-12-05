(_ => {
let overlay;
let dialogue;
let form;
let csrf_token;

document.addEventListener('motd.form.init', (e) => {
    const init_data = e.detail;
    overlay = overlays_stack.end();
    dialogue = overlay.$dialogue[0];
    form = overlay.$dialogue.$body[0].querySelector('form');
    csrf_token = init_data.csrf_token;

    const switcher = Object.fromEntries(Object.entries(init_data.switcher).map(
        ([elementid, data]) => [elementid, new CViewSwitcher(elementid, 'change', data)]
    ));

    dialogue.addEventListener('dialogue.submit', handleResponse, {once: true});
    updateViewSwitchers();

    form.addEventListener('change', e => {
        if (e.target.matches('[name="repeat_end"]') || e.target.matches('[name="repeat"]')) {
            updateViewSwitchers();
        }
    });

    function updateViewSwitchers() {
        switcher.repeat.mainObj.value = switcher.repeat.mainObj.querySelector(':checked').value;
        switcher.repeat_end.mainObj.value = switcher.repeat_end.mainObj.querySelector(':checked').value;
        switcher.repeat_end.rebuildView.apply(switcher.repeat_end);
        switcher.repeat.rebuildView.apply(switcher.repeat);
    }
});

document.addEventListener('motd.form.edit', ({detail}) => PopUp('motd.form.edit', detail, {dialogueid: 'motd-form-edit', dialogue_class: 'modal-popup-generic'}));
document.addEventListener('motd.form.submit', doFormAction);
document.addEventListener('motd.form.delete', doFormAction);

document.addEventListener('DOMContentLoaded', () => document.querySelector('form[name="js-motd-list"]').onsubmit = (ev) => {
    ev.preventDefault();

    const data = getFormFields(ev.target);

    fetch(`?action=${ev.submitter.getAttribute('formaction')}`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(detail => handleResponse({detail}))
    .catch(x => handleResponse({detail: {error: {title: t('Unexpected server error.'), messages:[]}}}))

    return false;
});

function handleResponse({detail}) {
    if (detail.error) {
        detail.error.title && postMessageError(detail.error.title);
        postMessageDetails('error', detail.error.messages);
    }
    if (detail.success) {
        chkbxRange.clearSelectedOnFilterChange();
        postMessageOk(detail.success.title);
    }

    window.location.reload(true);
}

function doFormAction({detail}) {
    const data = getFormFields(form);
    const action = detail.button.mvc_action;
    const mvc_data = detail.mvc_data??{};
    mvc_data[csrf_token[action][0]] = csrf_token[action][1];

    fetch(`?action=${action}`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({...mvc_data, ...data})
    })
    .then((response) => response.json())
    .then((response) => {
        if ('error' in response) {
            throw {error: response.error};
        }

        overlayDialogueDestroy(overlay.dialogueid);
        dialogue.dispatchEvent(new CustomEvent('dialogue.submit', {detail: {action, ...response}}));
    })
    .catch((exception) => {
        form.parentNode.querySelectorAll('.msg-good,.msg-bad,.msg-warning').forEach(node => node.remove());

        let title, messages;

        if (typeof exception === 'object' && 'error' in exception) {
            title = exception.error.title;
            messages = exception.error.messages;
        }
        else {
            messages = [t('Unexpected server error.')];
        }

        const message_box = makeMessageBox('bad', messages, title)[0];

        form.parentNode.insertBefore(message_box, form);
    })
    .finally(_ => overlay.unsetLoading());
}
})();
