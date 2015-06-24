Creating an osTicket distribution
=================================
osTicket is packaged using an included script. Access to the packaging
system is provided via the `manage.php` CLI app.

The packaging system extends the deployment system in order to remove
similar code between the two processes. Where possible, the files reported
to be part of the git repository are used in the packaging process, which
removes the possibility of experimental files and those ignored by git from
being added to the distribution.

More information is available via the automated help output.

    php manage.php package --help

Creating the ZIP file
---------------------
To package the system using the defaults (as a ZIP file), just invoke the
packager with no other options.

    php manage.php package
