=== Updraft ===
Contributors: reaperhulk
Donate link: http://langui.sh/updraft-wp-backup-restore
Tags: backup, restore, database, rackspace, cloudfiles, cloud, amazon, s3, ftp, cloud
Requires at least: 3.0
Tested up to: 3.0
Stable tag: 0.6

Updraft - Backup/Restore is a plugin aimed at simplifying backups (and restoration) for your blog. Backup into the cloud (Cloud Files, S3, FTP, and more) and restore with a single click!

== Description ==

Updraft - Backup/Restore is a plugin designed to back up your WordPress blog.  Uploads, themes, plugins, and even your DB can be backed up to Rackspace Cloud Files, Amazon S3, sent to an FTP server, or even emailed to you on a scheduled basis.  Plus, restoring is just a click away! Catch new releases and other information about my plugins by following <a href="http://twitter.com/reaperhulk" target="_blank">@reaperhulk</a> on Twitter.

== Installation ==

1. Upload updraft/ into wp-content/plugins/
2. Activate the plugin via the 'Plugins' menu.
3. Go to the 'Updraft' option under settings.
4. Follow the instructions.

= I found a bug. What do I do? =

Contact me!  This is a complex plugin and the only way I can ensure it's robust is to get bug reports and fix the problems that crop up.  Please include as much information as you can when reporting (PHP version, your blog's site, the error you saw and how you got to the page that caused it, et cetera).

== Changelog ==
= v0.6 - 7/27/2010 =
* Added blog name to backup names
* Fixed a short tag and private declaration that caused issues activating the plugin for some users.

= v0.5 - 6/17/2010 =
* First public release!
* Fixed up WP_Filesystem calls for restoration and deletion of -old directories.
* Too many other improvements to count.

= v0.4- 5/23/2010 =
* Resumed work now that 3.0 is almost out. 3.0 required due to some bugs present in 2.9 around the WP_Filesystem chmod.

= v0.3 - 2/5/2010 =
* Rewrote restore functionality to use WP_Filesystem classes. (still needs work)

= v0.2 - 1/14/2010 =
* Partial restore functionality (DB restore not yet functional)
* Improved UI
* Improved error handling

= v0.1 - 1/2/2010 =
* Initial versioning.  Seeded 1/12/2010 to testers.


== Screenshots ==

1. Updraft configuration page


== License ==

    Copyright 2010 Paul Kehrer

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

