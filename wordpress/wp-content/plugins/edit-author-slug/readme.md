# Edit Author Slug [![Build Status](https://travis-ci.org/thebrandonallen/edit-author-slug.png?branch=master)](https://travis-ci.org/thebrandonallen/edit-author-slug)
**Contributors:** thebrandonallen  
**Tags:** author, author base, author slug, user nicename, nicename, permalink, permalinks, slug, users, user, role, roles  
**Requires at least:** 3.6.1  
**Tested up to:** 3.8.1  
**Stable tag:** 1.0

Allows an admin (or capable user) to edit the author slug of a user, and change the author base.

## Description

This plugin allows an Admin to change the author slug (a.k.a. - nicename), without having to actually enter the database. You can also change the Author Base (the '/author/' portion of the author URLs). Two new fields will be added to your Dashboard. The "Edit Author Slug" field can be found under Users > Your Profile or Users > Authors & Users (Users > Users in WP 3.0). The "Author Base" field can be found under Settings > Edit Author Slug. This allows you to craft the perfect URL structure for you Author pages. For your convenience, an Author Slug column is added to make it easier to determine if one needs to change the Author Slug.

WordPress default structure  
http://example.com/author/username/

Edit Author Slug allows for  
http://example.com/ninja/master-ninja/

or using a role-based author base  
http://example.com/ida/master-splinter/ (for an Administrator Role)  
http://example.com/koga/leonardo/ (for a Subscriber Role)

#### Translations Available
* Dutch - props Juliette Reinders Folmer

You can also visit the plugin's homepage at http://brandonallen.org/wordpress/plugins/edit-author-slug/

## Installation

1. Upload `edit-author-slug` folder to your WordPress plugins directory (typically 'wp-content/plugins')
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Users > Your Profile, or Users > All Users > (username), and edit the author slug.
1. Click "Update Profile" or "Update User"
1. Go to Settings > Edit Author Slug to edit settings
1. Click "Save Changes"

## Frequently Asked Questions

#### Why can't I edit my Author Slug?

Make sure you are an admin, or have been assigned the `edit_users` or `edit_author_slug` capability.

#### Why isn't my new Author Slug working?

While I've made every attempt to prevent this, I may have missed a spot or two. First things first, go to Settings > Permalinks and click "Save Changes." You don't need to actually need to make any changes for this to work. Hopefully, this should kick your new Author Slug into gear.

If this doesn't work, make sure you don't have any slug conflicts from other posts/pages/plugins/permalink setting/etc. If you're still experiencing the issue, feel free to post a support request in the forums.

## Changelog

#### 1.0
* Added ability to do role-based author bases
* Added ability to use role-based author templates
* Moderate code refactoring
* Various code fixes and improvements
* Add "nickname" as option for auto-update
* First pass at unit test (only checks if the plugin is installed, for now)

#### 0.9.6
* Fixed loading of translation files. Looks in wp-content/plugins/edit-author-slug/languages. If you're running 3.7+ (and you are... aren't you?), it will fall back to wp-content/languages/plugins if a proper localization can't be found in the edit-author-slug folder.

#### 0.9.5
* Fixed instances where the Author Base wouldn't change, or would result in a 404

#### 0.9.4
* Update readme references to plugin settings
* Fix some copy pasta in settings
* Update screenshots

#### 0.9.3
* Quickly caught a few things I missed, so this release was skipped. See 0.9.4 for changes

#### 0.9.2
* Fix issue where any profile information other than the Author Slug could not be updated
* Minor code improvement

#### 0.9.1
* Add 'Settings' link to plugins list table

#### 0.9
* Allow Author Slug to be automatically created/updated based on a defined structure
* Switched to using the Settings API, which also means that all options moved to the Settings > Edit Author Slug page
* Various code improvements/optimizations

#### 0.8.1
* Fix a bug that prevented non-admin users from updating their profile

#### 0.8
* Drastically improved error handling and feedback for author slug editing.
* Restore duplicate author slug check as old method could alter the slug without any sort of warning.
* Further improve the logic for flushing rewrite rules.
* Introduce ba_eas_can_edit_author_slug() and matching filter to make it even easier to give users the ability to update their own author slug.
* Add message in plugins list warning users of WP less than 3.2 that 0.8 is the last update they'll receive.

#### 0.7.2
* Remove overzealous cap check.

#### 0.7.1
* Fix some unfortunate errors I missed before tagging 0.7.

#### 0.7
* Significant code refactoring.
* Added custom capability to give site admins the ability to add author slug access to other roles.
* Improvements/optimizations to code logic.
* Fixed an incorrect textdomain string.
* Removed filter added in 0.6 as it was messy. It's much easier to achieve the same result without the plugin.
* Got rid of wp_die() statement on duplicate author slugs in favor of WP's built-in duplicate author slug method.

#### 0.6.1
* Added Dutch translation - props Juliette Reinders Folmer.
* Don't hard code the languages folder path.
* Improve class check/initialization.

#### 0.6
* Some code cleanup.
* More security hardening.
* Added filter to allow for the complete removal of the Author Base (http://brandonallen.org/2010/11/03/how-to-remove-the-author-base-with-edit-author-slug/).
* Flush rewrite rules only when necessary instead of every page load.

#### 0.5
* Added 'Author Slug' column to Users > Authors & Users (Users > Users in 3.0) page (props Yonat Sharon for the jumpstart).
* Ended support for the WP 2.8 branch. Most likely still works, but I will not support it.
* Various bug fixes.

#### 0.4
* Added ability to change the Author Base.
* Updated documentation.
* Added some extra security via WP esc_* functions.
* Added Belorussian translation, props Marcis G.

#### 0.3.1
* Added Hebrew Translation, props Yonat Sharon.

#### 0.3
* Now localization friendly.

#### 0.2.1
* Fixed a bug that prevented updating a user if the author slug did not change.

#### 0.2
* Added a check to avoid duplicate slugs.
* Properly sanitize slug before comparison and database insertion.
* Updated plugin URI.

#### 0.1.4
* Update tags to reflect WordPress 2.9.1 compatability.
* Update link to plugin homepage.

#### 0.1.3
* Update tags to reflect WordPress 2.9 compatability.

#### 0.1.2
* Fix version number issues.

#### 0.1.1
* Remove extra debug functions left behind.
* Add screenshot.

#### 0.1
* Initial release.

## Upgrade Notice

#### 1.0
Role-based author bases are here!

#### 0.4
Adds ability to change the Author Base (not a required upgrade)

#### 0.3
Edit Author Slug can now be localized. You can find edit-author-slug.pot in 'edit-author-slug/languages' to get you started.

#### 0.2
Added a check to avoid duplicate duplicate author slugs, and better sanitization.

## TODO
* Allow Author Slug editing of users from one centralized location
