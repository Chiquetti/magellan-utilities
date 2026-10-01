/* Validação e envio dos formulários do site (EN / ES / PT).
   Mesmo fluxo da página Careers do Magellan Group: valida no navegador, envia por fetch,
   mostra erro em cada campo e só segue para a página de obrigado quando o servidor confirma. */
(function () {
  'use strict';

  var LANG = (document.documentElement.lang || 'en').slice(0, 2);
  if (['en', 'es', 'pt'].indexOf(LANG) === -1) LANG = 'en';
  var MAX_MB = 10;
  var EXT = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'rtf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'zip', 'dwg', 'dxf', 'kmz', 'kml'];
  var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

  var M = {
    en: { req: 'This field is required.', consent: 'Please check this box to continue.', email: 'That email address does not look right.', phone: 'That phone number looks incomplete. Please include the area code.',
      ftype: 'That file type is not accepted.', fsize: 'The files are too large. Up to ' + MAX_MB + ' MB in total.',
      fix1: 'Please fix the field marked below and send again.', fixN: function (n) { return 'Please fix the ' + n + ' fields marked below and send again.'; },
      sending: 'Sending…', generic: 'Something went wrong. Please try again, or call us.', network: 'We could not reach the server. Check your connection and try again, or call us.' },
    es: { req: 'Este campo es obligatorio.', consent: 'Marque esta casilla para continuar.', email: 'Ese correo no parece válido.', phone: 'Ese teléfono parece incompleto. Incluya el código de área.',
      ftype: 'Tipo de archivo no aceptado.', fsize: 'Los archivos son muy grandes. Máximo ' + MAX_MB + ' MB en total.',
      fix1: 'Corrija el campo marcado abajo y envíe de nuevo.', fixN: function (n) { return 'Corrija los ' + n + ' campos marcados abajo y envíe de nuevo.'; },
      sending: 'Enviando…', generic: 'Algo salió mal. Inténtelo de nuevo o llámenos.', network: 'No pudimos conectar con el servidor. Revise su conexión e inténtelo de nuevo, o llámenos.' },
    pt: { req: 'Este campo é obrigatório.', consent: 'Marque esta caixa para continuar.', email: 'Esse e-mail não parece válido.', phone: 'Esse telefone parece incompleto. Inclua o código de área.',
      ftype: 'Tipo de arquivo não aceito.', fsize: 'Os arquivos são muito grandes. Máximo de ' + MAX_MB + ' MB no total.',
      fix1: 'Corrija o campo marcado abaixo e envie novamente.', fixN: function (n) { return 'Corrija os ' + n + ' campos marcados abaixo e envie novamente.'; },
      sending: 'Enviando…', generic: 'Algo deu errado. Tente novamente ou ligue para nós.', network: 'Não conseguimos conectar ao servidor. Verifique sua conexão e tente novamente, ou ligue para nós.' }
  }[LANG];

  // estilos dos erros (injetados aqui para não depender do CSS de cada site)
  var css = '.fv-alert{background:#fdf1f0;border-left:3px solid #b3261e;color:#7a1712;padding:12px 16px;font-size:15px;border-radius:0 2px 2px 0;margin:0 0 14px}' +
    '.fv-err{margin:4px 0 0;font-size:13.5px;font-weight:600;color:#b3261e}' +
    '.fv-bad{border-color:#b3261e!important;outline:2px solid #f6d5d2}' +
    'form button[disabled]{opacity:.65;cursor:progress}';
  var st = document.createElement('style'); st.textContent = css; document.head.appendChild(st);

  function validate(form) {
    var errors = {}, total = 0, i, el, v;
    for (i = 0; i < form.elements.length; i++) {
      el = form.elements[i];
      if (!el.name || el.name === 'website' || el.disabled) continue;
      if (el.type === 'file') {
        Array.prototype.forEach.call(el.files || [], function (f) {
          var ext = (f.name.split('.').pop() || '').toLowerCase();
          if (EXT.indexOf(ext) === -1) errors[el.name] = M.ftype;
          total += f.size;
        });
        continue;
      }
      if (el.type === 'checkbox') { if (el.required && !el.checked) errors[el.name] = M.consent; continue; }
      if (el.type === 'hidden' || el.type === 'submit' || el.type === 'button') continue;
      v = (el.value || '').trim();
      if (el.required && v === '') { errors[el.name] = M.req; continue; }
      if (v === '') continue;
      if (el.type === 'email' && !EMAIL_RE.test(v)) errors[el.name] = M.email;
      if (el.type === 'tel') { var d = v.replace(/\D/g, ''); if (d.length < 7 || d.length > 15) errors[el.name] = M.phone; }
    }
    if (total > MAX_MB * 1024 * 1024) {
      for (i = 0; i < form.elements.length; i++) { el = form.elements[i]; if (el.type === 'file' && el.files && el.files.length) { errors[el.name] = M.fsize; break; } }
    }
    return errors;
  }

  function init(form) {
    var alertBox = document.createElement('div');
    alertBox.className = 'fv-alert'; alertBox.setAttribute('role', 'alert'); alertBox.hidden = true;
    form.insertBefore(alertBox, form.firstChild);
    var button = form.querySelector('button[type="submit"]');
    var label = button ? button.textContent : '';

    function clear() {
      alertBox.hidden = true; alertBox.textContent = '';
      Array.prototype.forEach.call(form.querySelectorAll('.fv-err'), function (p) { p.remove(); });
      Array.prototype.forEach.call(form.querySelectorAll('.fv-bad'), function (e) { e.classList.remove('fv-bad'); e.removeAttribute('aria-invalid'); });
    }
    function show(errors) {
      clear();
      var first = null, general = [], n = 0;
      Object.keys(errors).forEach(function (k) {
        var el = form.elements[k];
        if (!el || k === '_form') { general.push(errors[k]); return; }
        var holder = (el.type === 'checkbox' ? (el.closest('label') || el).parentNode : (el.closest('.field') || el.parentNode));
        var p = document.createElement('p'); p.className = 'fv-err'; p.textContent = errors[k]; holder.appendChild(p);
        el.classList.add('fv-bad'); el.setAttribute('aria-invalid', 'true');
        if (!first) first = el; n++;
      });
      if (n) general.unshift(n === 1 ? M.fix1 : M.fixN(n));
      if (general.length) { alertBox.textContent = general.join(' '); alertBox.hidden = false; }
      if (first && first.focus) first.focus(); else alertBox.scrollIntoView({ block: 'center' });
    }
    function busy(on) { if (button) { button.disabled = on; button.textContent = on ? M.sending : label; } }

    form.addEventListener('input', function (e) {
      var el = e.target;
      if (el.classList && el.classList.contains('fv-bad')) {
        el.classList.remove('fv-bad'); el.removeAttribute('aria-invalid');
        var holder = (el.type === 'checkbox' ? (el.closest('label') || el).parentNode : (el.closest('.field') || el.parentNode));
        var p = holder.querySelector('.fv-err'); if (p) p.remove();
      }
    });
    form.addEventListener('change', function (e) { if (e.target.type === 'checkbox') form.dispatchEvent(new Event('input')); });

    form.addEventListener('submit', function (e) {
      var errors = validate(form);
      if (Object.keys(errors).length) { e.preventDefault(); show(errors); return; }
      if (!window.fetch || !window.FormData) return;   // sem fetch: envio normal; o servidor valida
      e.preventDefault(); clear(); busy(true);
      var body = new FormData(form);
      // A HostGator responde 409 com um mini-script que grava um cookie ("humans_...") quando a
      // requisição não o tem. O navegador resolve sozinho numa página normal; no fetch aplicamos
      // o cookie e repetimos uma vez.
      function post(retry) {
        return fetch(form.action, { method: 'POST', body: body, headers: { 'Accept': 'application/json' } }).then(function (res) {
          if (res.status === 409 && retry) {
            return res.text().then(function (txt) {
              var m = txt.match(/document\.cookie\s*=\s*"([^"=]+=[^";]*)"/);
              if (!m) throw new Error('blocked');
              document.cookie = m[1] + '; path=/';
              return post(false);
            });
          }
          return res.json().then(function (data) { return { status: res.status, data: data }; });
        });
      }
      post(true).then(function (r) {
        if (r.data && r.data.ok && r.data.redirect) { window.location.href = r.data.redirect; return; }
        busy(false);
        show((r.data && r.data.errors) || { _form: M.generic });
      }).catch(function () { busy(false); show({ _form: M.network }); });
    });
  }

  Array.prototype.forEach.call(document.querySelectorAll('form[data-validate]'), init);
})();
