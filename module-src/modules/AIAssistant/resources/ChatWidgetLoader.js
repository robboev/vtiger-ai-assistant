/**
 * AI Assistant Chat Widget Loader
 * Fetches ONLY the widget HTML (not a full vtiger page) and injects it.
 */
(function() {
    'use strict';

    // Don't load on login, logout, or install pages
    var href = window.location.href;
    if (href.indexOf('action=Login') !== -1 ||
        href.indexOf('view=Login') !== -1 ||
        href.indexOf('action=Logout') !== -1 ||
        href.indexOf('module=Install') !== -1 ||
        href.indexOf('view=ChatWidgetLoader') !== -1) {
        return;
    }

    // Prevent double-loading
    if (document.getElementById('ai-assistant-widget')) return;

    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'index.php?module=AIAssistant&action=ChatWidget', true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            // Prevent double injection
            if (document.getElementById('ai-assistant-widget')) return;

            var container = document.createElement('div');
            container.innerHTML = xhr.responseText;
            document.body.appendChild(container);

            // Execute inline scripts
            var scripts = container.querySelectorAll('script');
            for (var i = 0; i < scripts.length; i++) {
                var s = scripts[i];
                var ns = document.createElement('script');
                ns.textContent = s.textContent;
                document.body.appendChild(ns);
                s.remove();
            }
        }
    };
    xhr.send();
})();
