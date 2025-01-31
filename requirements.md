# WordPress to Markdown Exporter Requirements

## Core Functionality
- Convert WordPress posts/custom post types to Markdown format
- Only convert the post content (no metadata)
- Generate a zip file containing the Markdown files
- Store zip files in WordPress uploads directory, organized by date
- Maintain archive of previously generated zip files (no limit)
- Generate a log file in the zip listing successful and failed conversions

## User Interface
- Add a new menu item in WordPress admin panel
- Main screen should show:
  - Post type selection dropdown (exports all posts of selected type)
  - Option to add date prefix (YYYY-MM-DD)
  - Export button
  - List of previously generated zip files with download links
  - Cleanup button to remove all previous zip files (no confirmation needed)
- Show progress as number of posts processed

## Technical Requirements
### File Handling
- Convert HTML content to Markdown
  - Ignore embedded content (iframes, etc)
  - Ignore HTML comments
  - Convert blocks to equivalent markdown or plain text
- Optional date prefix format: YYYY-MM-DD-{filename}
  - If multiple exports on same day, newer export overwrites previous
- Base filename from post slug (using WordPress default sanitization)
- Store zip files in organized folder structure by date

### File Listing
- Display creation date of zip files
- Show file size
- Provide direct download links

## Security & Performance
- Restrict access to WordPress administrators only
- Handle large numbers of posts efficiently
- Implement timeout prevention for large exports

## WordPress Integration
- Compatible with WordPress 5.0+
- Follow WordPress coding standards
- Use WordPress file system APIs

## Plugin Structure 
```
wordpress-to-markdown-exporter/
├── admin/
│ ├── class-admin-page.php
│ └── views/
├── includes/
│ ├── class-converter.php
│ ├── class-file-handler.php
│ └── class-exporter.php
└── wordpress-to-markdown-exporter.php
```
