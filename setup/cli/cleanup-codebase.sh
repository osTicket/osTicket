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

# Removed in v1.7.1
include/class.mcrypt.php
include/client/thankyou.inc.php
include/upgrader/sql/00ff231f-9f3b454c.patch.sql
include/upgrader/sql/02decaa2-60fcbee1.patch.sql
include/upgrader/sql/15719536-dd0022fb.patch.sql
include/upgrader/sql/15af7cd3-98ae1ed2.patch.sql
include/upgrader/sql/15b30765-dd0022fb.cleanup.sql
include/upgrader/sql/15b30765-dd0022fb.patch.sql
include/upgrader/sql/1da1bcba-15b30765.patch.sql
include/upgrader/sql/2e20a0eb-98ae1ed2.patch.sql
include/upgrader/sql/2e7531a2-d0e37dca.patch.sql
include/upgrader/sql/32de1766-852ca89e.patch.sql
include/upgrader/sql/435c62c3-2e7531a2.cleanup.sql
include/upgrader/sql/435c62c3-2e7531a2.patch.sql
include/upgrader/sql/49478749-c2d2fabf.patch.sql
include/upgrader/sql/522e5b78-02decaa2.patch.sql
include/upgrader/sql/60fcbee1-f8856d56.patch.sql
include/upgrader/sql/7be60a84-522e5b78.patch.sql
include/upgrader/sql/852ca89e-740428f9.patch.sql
include/upgrader/sql/98ae1ed2-e342f869.cleanup.sql
include/upgrader/sql/98ae1ed2-e342f869.patch.sql
include/upgrader/sql/9f3b454c-c0fd16f4.patch.sql
include/upgrader/sql/a67ba35e-98ae1ed2.patch.sql
include/upgrader/sql/aa4664af-b19dc97d.patch.sql
include/upgrader/sql/abe9c0cb-bbb021fb.patch.sql
include/upgrader/sql/aee589ab-98ae1ed2.patch.sql
include/upgrader/sql/b19dc97d-435c62c3.patch.sql
include/upgrader/sql/bbb021fb-49478749.patch.sql
include/upgrader/sql/c00511c7-7be60a84.cleanup.sql
include/upgrader/sql/c00511c7-7be60a84.patch.sql
include/upgrader/sql/c0fd16f4-d959a00e.patch.sql
include/upgrader/sql/c2d2fabf-aa4664af.patch.sql
include/upgrader/sql/d0e37dca-1da1bcba.patch.sql
include/upgrader/sql/d959a00e-32de1766.patch.sql
include/upgrader/sql/dd0022fb-f4da0c9b.patch.sql
include/upgrader/sql/e342f869-c00511c7.patch.sql
include/upgrader/sql/f4da0c9b-00ff231f.patch.sql
include/upgrader/sql/f8856d56-abe9c0cb.patch.sql

# Removed in v1.7.2
include/pear/Crypt/Random.php

# Removed in v1.7.3
include/index.html
"
