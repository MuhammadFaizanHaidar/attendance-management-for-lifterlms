# Attendance Management For LifterLMS

[![License: GPLv3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0.html)
[![WordPress](https://img.shields.io/badge/WordPress-4.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.2%2B-blue.svg)](https://php.net/)
[![LifterLMS](https://img.shields.io/badge/LifterLMS-Required-orange.svg)](https://lifterlms.com/)

Comprehensive attendance management system for LifterLMS with advanced reporting dashboard, custom database tables, and role-based attendance marking. Perfect for educational institutions with thousands of students.

## 📋 Description

The Attendance Management For LifterLMS plugin provides a comprehensive attendance management system for LifterLMS-powered educational platforms. This major update (v2.0.0) introduces powerful new features designed for scalability and advanced reporting.

### 🆕 New in Version 2.0.0

- **📊 Advanced Reporting Dashboard** with interactive charts and analytics
- **🗄️ Custom Database Tables** for improved performance with large datasets
- **🔄 Migration System** to seamlessly upgrade from meta-based storage
- **👥 Role-Based Attendance Marking** allowing instructors to mark student attendance
- **📤 Export Functionality** for attendance data (CSV, PDF)
- **⚡ Performance Optimization** for sites with thousands of students
- **🛡️ Graceful Degradation** ensuring compatibility across all environments

## ✨ Features

### Core Attendance Management

- ✅ Students can mark their attendance and view attendance statistics (count & percentages)
- ✅ Admin can enable/disable the addon from LifterLMS settings page
- ✅ Course-specific attendance control (allow/disallow per course)
- ✅ Admin can view and manage student attendance in course edit pages
- ✅ Global attendance settings for all courses
- ✅ Shortcode support for displaying attendance information
- ✅ Data cleanup options on plugin uninstall

### Advanced Features (v2.0.0)

- **📊 Interactive Reporting Dashboard** with Chart.js integration
  - Monthly attendance rate charts
  - Course-specific statistics
  - Student attendance analytics
  - Export capabilities

- **🗄️ Custom Database Architecture**
  - Optimized `wp_llmsat_attendance` table
  - Improved query performance
  - Better scalability for large datasets

- **🔄 Hybrid Data Management System**
  - Seamless migration from meta-based storage
  - Backward compatibility with existing data
  - Automatic data source switching

- **👥 Role-Based Attendance Marking**
  - Instructors can mark student attendance
  - Configurable role permissions
  - Admin control over who can mark attendance

- **📧 Email Notifications**
  - Low attendance alerts
  - Automated notifications to instructors
  - Customizable alert thresholds

- **🧪 Performance Testing Framework**
  - Built-in testing tools
  - Test data generation
  - Performance benchmarking

### Technical Improvements

- Hybrid data management system (meta + custom tables)
- Graceful degradation when custom tables unavailable
- Enhanced error handling and validation
- Improved database indexing for faster queries
- Memory usage optimization
- WordPress coding standards compliance
- Full translation support (`.pot` file included)

## 📦 Installation

### Minimum System Requirements

- **PHP:** 7.2 or later
- **MySQL:** 5.6 or later
- **WordPress:** 4.0 or later
- **LifterLMS:** Latest version recommended

### Installation Steps

1. Upload the plugin files to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to **LifterLMS → Settings → Integrations → Attendance** to configure the plugin
4. (Optional) If upgrading from v1.x, use the **Migration** tool to move data to the custom table

## 🚀 Quick Start

### For Administrators

1. **Enable Global Attendance:**
   - Go to `LifterLMS → Settings → Integrations → Attendance`
   - Enable "Global Attendance" option
   - Configure role-based permissions

2. **View Reports:**
   - Navigate to `LifterLMS → Attendance Reports`
   - View interactive charts and analytics
   - Export data as needed

3. **Manage Course Attendance:**
   - Edit any LifterLMS course
   - View student attendance in the metabox
   - Mark attendance for students (if enabled)

### For Students

1. **Mark Attendance:**
   - Visit any enrolled course page
   - Click the "Mark Present" button
   - View your attendance statistics

2. **View Attendance:**
   - Use shortcodes to display attendance information
   - Check attendance percentages and counts

## 📸 Screenshots

1. Plugin settings
2. Enable/disable plugin
3. Delete attendance data on uninstall
4. Students can mark their attendances by clicking this button
5. Listing of students attendance in course edit pages
6. Shortcodes to display attendance information
7. Display Attendance Information using shortcodes
8. Admin can disallow attendance on specific courses
9. Advanced reporting dashboard with charts

## 🔧 Configuration

### Global Settings

Navigate to **LifterLMS → Settings → Integrations → Attendance**:

- **Enable Global Attendance:** Turn on attendance for all courses
- **Role-Based Permissions:** Select which roles can mark attendance
- **Delete Data on Uninstall:** Remove all attendance data when plugin is deleted

### Course-Specific Settings

- Edit any course
- Use the "Disallow Attendance" checkbox to disable attendance for specific courses

### Migration

If upgrading from v1.x:

1. Navigate to **LifterLMS → Attendance Migration**
2. Review the migration status
3. Run migration to move data to custom table
4. Clean up old meta data after verification

## 📝 Shortcodes

### Display Student Attendance Count
```
[llmsat_student_attendance_count course_id="123"]
```

### Display Student Attendance Percentage
```
[llmsat_student_attendance_percentage course_id="123"]
```

### Display Top Attendant
```
[llmsat_top_attendant course_id="123"]
```

## 🔄 Upgrade Notice

### Version 2.0.0

**MAJOR UPDATE** - This version includes significant changes:

- **NEW:** Advanced reporting dashboard with interactive charts
- **NEW:** Custom database tables for improved performance
- **NEW:** Migration system to upgrade from meta-based storage
- **NEW:** Role-based attendance marking for instructors
- **NEW:** Export functionality (CSV, PDF)
- **NEW:** Performance optimization for thousands of students
- **NEW:** Email notifications for low attendance
- **IMPROVED:** Enhanced error handling and graceful degradation
- **IMPROVED:** Better database indexing and query optimization

### ⚠️ **IMPORTANT - Migration Required for Existing Users**

If you're upgrading from version 1.x to 2.0.0, you **MUST** run the data migration tool to move your attendance data from the old storage system to the new optimized database table.

#### **Step-by-Step Migration Guide:**

1. **After updating to version 2.0.0**, you'll see an admin notice at the top of your WordPress admin area prompting you to run the migration.

2. **Click the "Go to Migration Tool" button**, or navigate to:
   - **LifterLMS → Courses → Attendance Migration** from your WordPress admin menu

3. **Review the migration statistics** showing:
   - How many attendance records are in the old system (Meta Storage)
   - How many records are already in the new system (Custom Table)

4. **Click the "Start Migration" button** to begin the migration process.

5. **Wait for completion** - The migration will run in the background and show a progress bar. This may take a few minutes depending on the amount of data.

6. **Clean up old data** - After migration completes, click **"Clean Up Meta Data"** to remove the old data from user meta (optional but recommended for performance).

7. **Verify your data** - Check that everything is working correctly by:
   - Viewing student attendance records in course edit pages
   - Checking the attendance reports dashboard
   - Verifying student attendance statistics

#### **Important Notes:**

- ✅ **Safe Process**: The migration is non-destructive. Your data will remain in both locations until you choose to clean up.
- ✅ **No Downtime**: You can continue using the plugin normally during migration - it will work with both old and new data.
- ✅ **Backup Recommended**: We recommend backing up your database before running the migration (standard WordPress best practice).
- ✅ **Reversible**: If you encounter any issues, the migration can be run multiple times safely.

#### **For New Installations:**

If you're installing version 2.0.0 for the first time, **no migration is needed**. The plugin will automatically use the new database structure.

## 📋 Changelog

### 2.0.0 (Current)

- **MAJOR UPDATE:** Complete rewrite with advanced features
- **NEW:** Interactive reporting dashboard with Chart.js integration
- **NEW:** Custom database table architecture (`wp_llmsat_attendance`)
- **NEW:** Hybrid data management system (meta + custom tables)
- **NEW:** Data migration system with progress tracking
- **NEW:** Role-based attendance marking (instructor, lms_manager, administrator)
- **NEW:** Export functionality for attendance data (CSV, PDF)
- **NEW:** Performance testing framework
- **NEW:** Email notifications for low attendance alerts
- **NEW:** Graceful degradation when custom tables unavailable
- **IMPROVED:** Enhanced error handling and validation
- **IMPROVED:** Better database indexing for faster queries
- **IMPROVED:** Memory usage optimization
- **IMPROVED:** WordPress coding standards compliance
- **IMPROVED:** Plugin activation/deactivation handling
- **IMPROVED:** Settings page with role management options
- **FIXED:** Various bug fixes and stability improvements

### 1.0.3

- Tested with latest versions of WordPress and LifterLMS
- Added LifterLMS activation requirement in plugin boiler plate

### 1.0.2

- Added WordPress coding standards
- Added CSS style to Mark Present button
- Removed unused code
- Added function definitions

### 1.0.1

- Added compatibility with latest version of WordPress and LifterLMS
- Replaced text domain constant with string

### 1.0.0

- Initial release

## ❓ FAQ

### Can I use "Attendance Management For LifterLMS" addon and any other attendance addon simultaneously?

Yes, you can use "Attendance Management For LifterLMS" addon and any other attendance addon at the same time. The plugins operate independently.

### Will my existing attendance data be preserved when upgrading to v2.0.0?

Yes! The plugin includes a migration system that will move your existing data from user meta to the new custom table structure. Your data will be preserved and accessible.

### Can instructors mark attendance for students?

Yes! Version 2.0.0 includes role-based attendance marking. You can configure which roles (instructor, lms_manager, administrator) can mark attendance for students in the settings.

### Does the plugin work with large numbers of students?

Yes! Version 2.0.0 includes performance optimizations and custom database tables designed to handle thousands of students efficiently.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This plugin is licensed under the GPLv3 or later.

```
Copyright (C) 2025 Muhammad Faizan Haidar

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

## 👤 Author

**Muhammad Faizan Haidar**

- Website: [https://faizanhaidar.com](https://faizanhaidar.com)
- GitHub: [@MuhammadFaizanHaidar](https://github.com/MuhammadFaizanHaidar)

## 🙏 Credits

- Built for [LifterLMS](https://lifterlms.com/)
- Uses [Chart.js](https://www.chartjs.org/) for reporting dashboard
- Contributors: muhammadfaizanhaidar, fahdi, mustafazaidi

## 📞 Support

For support, feature requests, or bug reports, please visit:
- [GitHub Issues](https://github.com/MuhammadFaizanHaidar/attendance-management-for-lifterlms/issues)
- [WordPress.org Support](https://wordpress.org/support/plugin/attendance-management-for-lifterlms)

---

**Made with ❤️ for the LifterLMS community**
