== Attendance Management For LifterLMS ==
Contributors: muhammadfaizanhaidar, fahdi, mustafazaidi
Tags: lifterlms, attendance, mark, award, reward, engagement, submission, nomination, reporting, dashboard, analytics
Requires at least: 4.0
Donate link: https://faizanhaidar.com
Tested up to: 6.7.1
Stable tag: 2.0.0
License: GPLv2 or later
Requires PHP: 7.2
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Comprehensive attendance management system for LifterLMS with advanced reporting dashboard, custom database tables, and role-based attendance marking. Perfect for educational institutions with thousands of students.

== Description ==

The Attendance Management For LifterLMS addon provides a comprehensive attendance management system for LifterLMS-powered educational platforms. This major update (v2.0.0) introduces powerful new features designed for scalability and advanced reporting.

**New in Version 2.0.0:**
- **Advanced Reporting Dashboard** with interactive charts and analytics
- **Custom Database Tables** for improved performance with large datasets
- **Migration System** to seamlessly upgrade from meta-based storage
- **Role-Based Attendance Marking** allowing instructors to mark student attendance
- **Export Functionality** for attendance data (CSV, PDF)
- **Performance Optimization** for sites with thousands of students
- **Graceful Degradation** ensuring compatibility across all environments

**Core Features:**
- Students can mark their attendance and view their attendance statistics
- Admin can enable/disable the addon from LifterLMS settings
- Course-specific attendance control
- Admin attendance management in course edit pages
- Shortcode support for displaying attendance information
- Global attendance settings
- Data cleanup options

== Prerequisite: ==

- LifterLMS (Latest version recommended)

== Features: ==

**Core Attendance Management:**
- Students can mark their attendance and view attendance statistics (count & percentages)
- Admin can enable/disable the addon from LifterLMS settings page
- Course-specific attendance control (allow/disallow per course)
- Admin can view and manage student attendance in course edit pages
- Global attendance settings for all courses
- Shortcode support for displaying attendance information

**New Advanced Features (v2.0.0):**
- **Interactive Reporting Dashboard** with charts and analytics
- **Custom Database Architecture** for improved performance
- **Role-Based Attendance Marking** - instructors can mark student attendance
- **Data Migration System** - seamless upgrade from meta-based storage
- **Export Functionality** - export attendance data in multiple formats
- **Performance Optimization** - handles thousands of students efficiently
- **Email Notifications** - low attendance alerts
- **Testing Framework** - built-in performance testing tools

**Technical Improvements:**
- Hybrid data management system (meta + custom tables)
- Graceful degradation when custom tables unavailable
- Enhanced error handling and validation
- Improved database indexing for faster queries
- Memory usage optimization
- WordPress coding standards compliance

== Installation ==

#### Minimum System Requirements

Attendance Management For LifterLMS Requires

+ PHP 7.2 or later
+ MySQL 5.6 or later
+ WordPress 4.0 or later
Before installation please make sure you have latest LifterLMS installed.

1. Upload the plugin files to the /wp-content/plugins/ directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress

== Screenshots ==

1. Plugin settings 
2. Enable/disable plugin 
3. Delete attendance data on uninstall 
4. Students can mark their attendances by cliking this button 
5. Listing of students attendance in course edit pages 
6. Shortcodes to display attendance information 
7. Display Attendance Information using shortcodes 
8. Admin can disallow attendance on specific courses students will not be able to mark attendance
9. Disallow attendance

== FAQ ==

*Can I use “Attendance Management For LifterLMS” addon and any other attendance addon simultaneously?*

Yes, you can use “Attendance Management For LifterLMS”  addon and any other attendance addon at the same time.

== Upgrade Notice ==
*2.0.0*
- **MAJOR UPDATE**: Advanced reporting dashboard with interactive charts
- **NEW**: Custom database tables for improved performance with large datasets
- **NEW**: Migration system to upgrade from meta-based storage
- **NEW**: Role-based attendance marking for instructors
- **NEW**: Export functionality (CSV, PDF)
- **NEW**: Performance optimization for thousands of students
- **NEW**: Email notifications for low attendance
- **IMPROVED**: Enhanced error handling and graceful degradation
- **IMPROVED**: Better database indexing and query optimization

*1.0.3*
- Tested with latest versions of WordPress and LifterLMS.
- Added LifterLMS activation requirement in plugin boiler plate.

*1.0.2*
- Added WordPress coding standards.
- Added css style to Mark Present button.
- Removed unused code.
- Added function definations.

*1.0.1*
- Added compatibility with latest version of WordPress and LifterLMS.
- Replaced text domain constant with string.

*1.0.0*
- Intial release

== Changelog ==
*2.0.0*
- **MAJOR UPDATE**: Complete rewrite with advanced features
- **NEW**: Interactive reporting dashboard with Chart.js integration
- **NEW**: Custom database table architecture (`wp_llmsat_attendance`)
- **NEW**: Hybrid data management system (meta + custom tables)
- **NEW**: Data migration system with progress tracking
- **NEW**: Role-based attendance marking (instructor, lms_manager, administrator)
- **NEW**: Export functionality for attendance data (CSV, PDF)
- **NEW**: Performance testing framework
- **NEW**: Email notifications for low attendance alerts
- **NEW**: Graceful degradation when custom tables unavailable
- **IMPROVED**: Enhanced error handling and validation
- **IMPROVED**: Better database indexing for faster queries
- **IMPROVED**: Memory usage optimization
- **IMPROVED**: WordPress coding standards compliance
- **IMPROVED**: Plugin activation/deactivation handling
- **IMPROVED**: Settings page with role management options
- **FIXED**: Various bug fixes and stability improvements

*1.0.3*
- Tested with latest versions of WordPress and LifterLMS.
- Added LifterLMS activation requirement in plugin boiler plate.

*1.0.2*
- Added WordPress coding standards.
- Added css style to Mark Present button.
- Removed unused code.
- Added function definations.

*1.0.1*
- Added compatibility with latest version of WordPress and LifterLMS.
- Replaced text domain constant with string.

*1.0.0*
- Intial release