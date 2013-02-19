#!/bin/bash

find_root() {
    local root=".";
    while [[ ${#root} < 20 ]]; do
        [[ -f "$root/main.inc.php" ]] && break
        root="$root/.."
    done

    [[ ! -f "$root/main.inc.php" ]] && exit 1;

    pushd . > /dev/null
    cd $root
    pwd -P
    popd > /dev/null
}

root="$(find_root)"
if [[ ! -f "$root/main.inc.php" ]]; then
    echo "!!! Unable to determing codebase root."
    echo "!!! Try running this inside the codebase path."
    exit 1;
fi

while read file; do
    if [[ -n "$file" && "${file[0]}" != "\x23" && -f "$root/$file" ]]; then
        echo "Cleaning $file";
        rm "$root/$file";
    fi
done <<< "
# Removed in 1.6-rc5
ostconfig.php

# Removed in 1.6.0
images/button.jpg
images/logo.jpg
images/new_ticket_title.jpg
images/ticket_status_title.jpg
include/settings.php

# Removed in 1.7.0
api/urls.conf.php
css/font-awesome.css
images/bg.gif
images/fibres.png
images/home.gif
images/icons
images/lipsum.png
images/logo2.jpg
images/logout.gif
images/my_tickets.gif
images/new_ticket.gif
images/new_ticket_icon.jpg
images/poweredby.jpg
images/rainbow.png
images/refresh_btn.gif
images/ticket_status.gif
images/ticket_status_icon.jpg
images/verticalbar.jpg
images/view_closed_btn.gif
images/view_open_btn.gif
include/api.ticket.php
include/class.msgtpl.php
include/class.sys.php
include/client/index.php
include/client/viewticket.inc.php
include/ost-config.sameple.php
include/staff/api.inc.php
include/staff/changepasswd.inc.php
include/staff/dept.inc.php
include/staff/depts.inc.php
include/staff/editticket.inc.php
include/staff/mypref.inc.php
include/staff/myprofile.inc.php
include/staff/newticket.inc.php
include/staff/premade.inc.php
include/staff/reply.inc.php
include/staff/smtp.inc.php
include/staff/viewticket.inc.php
scp/css/autosuggest_inquisitor.css
scp/css/datepicker.css
scp/css/main.css
scp/css/style.css
scp/css/tabs.css
scp/images/alert.png
scp/images/bg-login-box.gif
scp/images/icons/email_settings.gif
scp/images/logo-support.gif
scp/images/minus.gif
scp/images/ostlogo.jpg
scp/images/pagebg.jpg
scp/images/plus.gif
scp/images/refresh.gif
scp/images/tab.jpg
scp/images/view_closed.gif
scp/images/view_open.gif
scp/js/ajax.js
scp/js/autolock.js
scp/js/bsn.AutoSuggest_2.1.3.js
scp/js/calendar.js
scp/js/datepicker.js
scp/js/tabber.js
"
