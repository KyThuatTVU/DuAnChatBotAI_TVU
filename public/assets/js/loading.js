/**
 * Page Loader – CELRAS TVU Chatbot
 * Hides the #page-loader overlay once the page has fully loaded.
 */
(function () {
    function hideLoader() {
        var el = document.getElementById('page-loader');
        if (!el) return;
        el.classList.add('loader-hidden');
        // Remove from DOM after transition ends
        setTimeout(function () { el.style.display = 'none'; }, 600);
    }

    if (document.readyState === 'complete') {
        // Already loaded (e.g. cached page)
        setTimeout(hideLoader, 350);
    } else {
        window.addEventListener('load', function () {
            setTimeout(hideLoader, 350);
        });
        // Fallback: hide after 4 s at the latest so loader never blocks
        setTimeout(hideLoader, 4000);
    }
})();
