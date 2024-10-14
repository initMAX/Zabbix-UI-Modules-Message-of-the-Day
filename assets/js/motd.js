document.addEventListener('DOMContentLoaded', () => {
    let message = cookie.read('motd_message');
    const bel = document.querySelector('header').nextElementSibling;

    if (message === null || bel === null) {
        return;
    }

    message = JSON.parse(base64_decode(message));
    const root = document.documentElement;
    root.style.setProperty('--motd-color-primary', '#'+message.color);

    const tmpl = new Template(`
        <div class="motd-box-details">
            <span>Active since:</span>
            <datetime>#{active_since}</datetime>
            <span>Active till:</span>
            <datetime>#{active_till}</datetime>
        </div>
    `);
    const el = makeMessageBox(message.type, [message.message], null, true)[0];

    el.classList.add('motd-box');
    el.querySelector('.msg-details').appendChild(tmpl.evaluateToElement(message));
    el.querySelector('.btn-overlay-close').addEventListener('click', () => el.remove());
    bel.parentNode.insertBefore(el, bel);

    function base64_decode(t){var r,e,o,c,f,a,d="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",h="",C=0;for(t=t.replace(/[^A-Za-z0-9\+\/\=]/g,"");C<t.length;)r=d.indexOf(t.charAt(C++))<<2|(c=d.indexOf(t.charAt(C++)))>>4,e=(15&c)<<4|(f=d.indexOf(t.charAt(C++)))>>2,o=(3&f)<<6|(a=d.indexOf(t.charAt(C++))),h+=String.fromCharCode(r),64!=f&&(h+=String.fromCharCode(e)),64!=a&&(h+=String.fromCharCode(o));utftext=h;for(var n="",i=(C=0,c1=c2=0);C<utftext.length;)(i=utftext.charCodeAt(C))<128?(n+=String.fromCharCode(i),C++):i>191&&i<224?(c2=utftext.charCodeAt(C+1),n+=String.fromCharCode((31&i)<<6|63&c2),C+=2):(c2=utftext.charCodeAt(C+1),c3=utftext.charCodeAt(C+2),n+=String.fromCharCode((15&i)<<12|(63&c2)<<6|63&c3),C+=3);return n}
});
