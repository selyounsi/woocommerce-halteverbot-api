jQuery(document).ready(function($) {
    const techData = {
        screen_resolution: screen.width + 'x' + screen.height,
        language: navigator.language,
        java_enabled: navigator.javaEnabled(),
        cookies_enabled: navigator.cookieEnabled,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
    };
    
    $.post(VisitorTrackerData.ajax_url, {
        action: 'track_visitor',
        nonce: VisitorTrackerData.nonce,
        referrer: document.referrer,
        url: window.location.href,
        userAgent: navigator.userAgent,
        pageTitle: document.title,
        techData: techData
    });
});