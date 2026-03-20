/**
 * AI Assistant Chat Widget Loader
 * Self-injects the chat widget into any vtiger page.
 * Added to Footer.tpl during module install.
 */
(function() {
    'use strict';

    // Don't load on login page or install pages
    if (window.location.href.indexOf('action=Login') !== -1 ||
        window.location.href.indexOf('action=Logout') !== -1 ||
        window.location.href.indexOf('module=Install') !== -1) {
        return;
    }

    // Fetch widget HTML from the module view
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'index.php?module=AIAssistant&view=ChatWidgetLoader', true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            // Create container and inject widget HTML
            var container = document.createElement('div');
            container.id = 'ai-assistant-container';
            container.innerHTML = xhr.responseText;
            document.body.appendChild(container);

            // Execute any inline scripts in the response
            var scripts = container.querySelectorAll('script');
            scripts.forEach(function(script) {
                var newScript = document.createElement('script');
                newScript.textContent = script.textContent;
                document.body.appendChild(newScript);
                script.remove();
            });
        }
    };
    xhr.send();
})();
