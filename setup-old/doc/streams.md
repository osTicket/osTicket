osTicket Database Migration
===========================

Database Upgrade Streams
------------------------
Database upgrade streams are registered in the
`INCLUDE_DIR/upgrader/streams/streams.cfg`. This file contains the names of
the upgrade streams in the order the streams should be applied in. The stock
osTicket install does not ship with a `streams.cfg`, so that updates to the
source code base will not destroy your `streams.cfg` file. If the file does
not exist, only the osTicket `core` stream will be updated.

Streams folders
---------------
Stream folders are created in the `INCLUDE_DIR/upgrader/streams` folder. The
name of the stream will be the name of the folder. For instance, the core
osTicket stream exists in the `core` folder, and so is called `core`. Each
stream folder should also have an accompanying hash file which gives the md5
hash of the tip of the stream. How you generate the hashes is up to you. For
the core stream, we use the md5 hash of the install SQL file. Changes made
to the main install file result in changes to the md5 hash of the file.
Then, the update file placed in the upgrade stream will have the changes
between to two hashes.

Upgrade Streams
---------------
Upgrade patches are used to migrate the database from snapshot to snapshot.
The system will start upgrading by consulting the `schema_signature`
configuration setting in the `config` table in the namespace of your stream
name. It will look for an upgrade patch file that starts with the first
eight characters of the current signature.

Each upgrade in a stream should set the `schema_signature` configuration
option in the `config` when completed. The plan is to make this automatic,
but currently, it is still a manual process. Whatever the hash of then last
executed patch is, it should be reflected in the config table at the
completion of the upgrade patch.

The migration process will continue until the hash reflected in the
`schema_signature` setting is the same as the value given in the stream
signature file. If no patch files are given to migrate from the current
`schema_signature` value to the value listed in the md5 file, then the
migrater will fail and complain that the system is not upgradeable.

Patch Files
-----------
Patch files should live in your stream folder and should have the name of

    12345678-00abcdef.patch.sql

and should contain only SQL text. Double-dash comments are only supported if
started at the beginning of a line. For instance, do not write then inline
as part of a long running SQL statement. The filename format is the first
eight chars of the starting and ending database `schema_signature` values.

Your patch process can be separated into two parts if you like. A cleanup file can be used to cleanup database objects after the completion of the patch process. Cleanup files must have the name of

    12345678-00abcdef.cleanup.sql

Where the starting and ending hashes are listed with a hyphen in between.
The idea is that PHP code can be run between the two SQL patch files.
//Currently, support for this is hardcoded, but will hopefully be redesigned
to include a `patch.php` file at some point in the future.//

If you want to use numeric serial numbers, make sure the first eight digits
change for every upgrade. For instance, use 00000001-00000002. Technically,
there is no current requirement for the hash file to be an actual md5 or even
have 32 hex chars in it.

Patch files should contain a header with some common information about the
patch. The header should be formatted similar to

    /* osTicket database migration patch
     *
     * @version 0.0.0
     * @signature 0123456789abcdef0000000000000000
     *
     * Details about the migration patch are listed here
     */

Eventually the `@signature` line will be automatically inspected and forced
into the config table for the `schema_signature` setting in the namespace of
your stream at the completion of the patch process. Please add it to your
patches to keep them future-minded. The `@version` is a string that will be
shown to clients during the upgrade process indicating the version numbers
as each patch is applied.

Customizing osTicket
====================
osTicket now supports database customizations using a separation technique
called *streams*. Separating the database upgrade path into streams allows
the upstream osTicket database upgrades to be kept separate from your own,
custom upgrade streams. Streams are registered in `streams.cfg` located in
the `UPGRADE_DIR/streams`.

Example streams.cfg
-------------------
    # Write the names of the stream folders to be enabled in this file.
    # The order is significant. The upgrade process will run updates for the
    # respective streams in the order they are listed in this file.

    core            # The upstream osTicket upgrade stream

    # Add custom upgrade streams here, which will be applied in the order
    # listed in this file

Database Customization Rules
---------------------------
1. Leave the upstream osTicket tables unchanged. If your customization makes
   changes to the main osTicket tables, you will likely get merge conflicts
   when the *core* stream is updated. If you need to add columns to an
   upstream table, add another table with the extra columns and link the
   data to the upstream table using the primary key of the upstream table.
   This will keep your data model separate from the upstream data model.
