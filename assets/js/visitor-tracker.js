jQuery(document).ready(function($) {
    // Einfache Version ohne techData Objekt
    $.post(VisitorTrackerData.ajax_url, {
        action: 'track_visitor',
        nonce: VisitorTrackerData.nonce,
        referrer: document.referrer,
        url: window.location.href,
        userAgent: navigator.userAgent,
        pageTitle: document.title
        // TechData weglassen oder separat senden
    });
});