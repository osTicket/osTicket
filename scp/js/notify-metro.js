$.notify.addStyle("metro", {
html:
        "<div>" +
            "<div class='image' data-notify-html='image'/>" +
            "<div class='text-wrapper'>" +
                "<div class='title' data-notify-html='title'/>" +
                "<div class='text' data-notify-html='text'/>" +
            "</div>" +
        "</div>",
    classes: {
        default: {
            "color": "#ffffff !important",
            "background-color": "#3f51b5",
            "border": "1px solid #3f51b5"
        },
        error: {
            "color": "#ffffff !important",
            "background-color": "#ee6e73",
            "border": "1px solid #ee6e73"
        },
        custom: {
            "color": "#ffffff !important",
            "background-color": "#3f51b5",
            "border": "1px solid #3f51b5"
        },
        success: {
            "color": "#ffffff !important",
            "background-color": "#66bb6a",
            "border": "1px solid #66bb6a"
        },
        info: {
            "color": "#ffffff !important",
            "background-color": "#29b6f6",
            "border": "1px solid #29b6f6"
        },
        warning: {
            "color": "#ffffff !important",
            "background-color": "#ffc107",
            "border": "1px solid #ffc107"
        },
        black: {
            "color": "#ffffff !important",
            "background-color": "#4c5667",
            "border": "1px solid #4c5667"
        },
        white: {
            "background-color": "#ffffff",
            "border": "1px solid #ddd"
        }
    }
});