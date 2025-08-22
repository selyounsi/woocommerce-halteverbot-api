jQuery(document).ready(function($) {
    $.post(VisitorTrackerData.ajax_url, {
        action: 'track_visitor',
        nonce: VisitorTrackerData.nonce,
        referrer: document.referrer,
        url: VisitorTrackerData.current_url,
        userAgent: navigator.userAgent
    });
});