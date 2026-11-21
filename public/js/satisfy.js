/* Satisfy admin: small enhancements over plain forms. No dependencies. */
(function () {
  'use strict';

  function each(selector, fn, root) {
    Array.prototype.forEach.call((root || document).querySelectorAll(selector), fn);
  }

  // Flash messages close.
  document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-dismiss]');
    if (!button) return;
    var flash = button.closest('.flash');
    flash.classList.add('is-leaving');
    setTimeout(function () { flash.remove(); }, 200);
  });

  // Filter a table's rows by the text in data-filter-text.
  each('[data-filter]', function (input) {
    var body = document.querySelector(input.getAttribute('data-filter'));
    if (!body) return;
    var empty = body.querySelector('[data-filter-empty]');
    input.addEventListener('input', function () {
      var needle = input.value.trim().toLowerCase();
      var shown = 0;
      each('tr[data-filter-text]', function (row) {
        var match = !needle || row.getAttribute('data-filter-text').indexOf(needle) !== -1;
        row.hidden = !match;
        if (match) shown++;
      }, body);
      if (empty) empty.hidden = shown !== 0;
    });
  });

  // Copy a code block.
  each('[data-copy]', function (button) {
    var target = document.querySelector(button.getAttribute('data-copy'));
    if (!target || !navigator.clipboard) { button.hidden = true; return; }
    var label = button.textContent;
    button.addEventListener('click', function () {
      navigator.clipboard.writeText(target.textContent).then(function () {
        button.textContent = 'Copied';
        setTimeout(function () { button.textContent = label; }, 1600);
      });
    });
  });

  // Build state, refreshed from the status endpoint.
  each('[data-build-status]', function (panel) {
    var url = panel.getAttribute('data-build-status');
    var interval = parseInt(panel.getAttribute('data-build-interval'), 10) || 5000;
    var state = panel.querySelector('[data-build-state]');
    var headline = panel.querySelector('[data-build-headline]');
    var detail = panel.querySelector('[data-build-detail]');
    var log = panel.querySelector('[data-build-log]');
    var lines = log && parseInt(log.getAttribute('data-build-log-lines'), 10);
    var follow = log && log.hasAttribute('data-follow');
    if (follow) log.scrollTop = log.scrollHeight;

    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function stamp(seconds) {
      var d = new Date(seconds * 1000);
      return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    function render(data) {
      var busy = data.building || data.pending > 0;
      state.setAttribute('data-state', busy ? 'busy' : 'idle');
      headline.textContent = data.building ? 'Building now' : (data.pending > 0 ? 'Queued, waiting for the worker' : 'No build running');
      var text = '';
      if (data.pending > 0) text += data.pending + (data.pending === 1 ? ' request' : ' requests') + ' waiting. ';
      if (data.updated) text += 'Last log written ' + stamp(data.updated) + '.';
      else if (!data.pending) text += 'No build has run yet.';
      detail.textContent = text.trim();
      if (log) {
        var body = (data.log || '').replace(/\s+$/, '');
        if (lines) body = body.split('\n').slice(-lines).join('\n');
        if (log.textContent !== body) {
          var atEnd = log.scrollTop + log.clientHeight >= log.scrollHeight - 8;
          log.textContent = body;
          if (follow && atEnd) log.scrollTop = log.scrollHeight;
        }
      }
    }

    function poll() {
      if (document.hidden) return;
      fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then(function (response) { return response.ok ? response.json() : null; })
        .then(function (data) { if (data) render(data); })
        .catch(function () {});
    }

    poll();
    setInterval(poll, interval);
    document.addEventListener('visibilitychange', poll);
  });

  // Collections: add and remove rows from the form's prototype.
  each('[data-prototype]', function (collection) {
    var prototype = collection.getAttribute('data-prototype');
    var name = collection.getAttribute('data-prototype-name') || '__name__';
    var pattern = new RegExp(name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g');
    var label = (document.getElementById(collection.id + '_label') || {}).textContent || 'row';
    var next = collection.children.length;

    function addRemove(row) {
      if (collection.getAttribute('data-allow-remove') !== '1') return;
      var remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'btn btn-quiet pair-remove';
      remove.textContent = 'Remove';
      remove.addEventListener('click', function () { row.remove(); });
      row.appendChild(remove);
    }

    Array.prototype.forEach.call(collection.children, addRemove);

    if (collection.getAttribute('data-allow-add') === '1') {
      var add = document.createElement('button');
      add.type = 'button';
      add.className = 'btn btn-sm collection-add';
      add.textContent = 'Add package';
      add.setAttribute('aria-label', 'Add a package to ' + label.trim().toLowerCase());
      add.addEventListener('click', function () {
        var holder = document.createElement('div');
        holder.innerHTML = prototype.replace(pattern, String(next++)).trim();
        var row = holder.firstElementChild;
        collection.appendChild(row);
        addRemove(row);
        var first = row.querySelector('input, select');
        if (first) first.focus();
      });
      collection.insertAdjacentElement('afterend', add);
    }
  });

  // The repository form's example: the entry satis.json will get, as you type.
  each('[data-repo-preview]', function (box) {
    var prefix = box.getAttribute('data-repo-preview');
    ['type', 'url', 'installationSource'].forEach(function (field) {
      var input = document.getElementById(prefix + '_' + field);
      var out = box.querySelector('[data-preview="' + field + '"]');
      if (!input || !out) return;
      input.addEventListener('input', function () {
        out.textContent = JSON.stringify(input.value);
        out.classList.remove('json-changed');
        void out.offsetWidth;
        out.classList.add('json-changed');
      });
    });
  });
})();
