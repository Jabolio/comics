#!/bin/bash

mysqldump --routines comics -u root -prootPassword > comics.sql
zip comics.zip _comics.cookie.txt comics.sql comics.php classes/* video-overlay.png public_html/*.php public_html/*.ico public_html/robots.txt public_html/css/* public_html/js/* public_html/images/* public_html/images/bg/* backup.sh
mutt -s "Comics stuff" -a comics.zip -- jpdeveaux@gmail.com < /dev/null
rm comics.zip
rm comics.sql
