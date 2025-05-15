# Crosspoint YouTube Downloader

A secure, internal-use YouTube downloader web application that supports both video (MP4) and audio (MP3) downloads.

## Features

- Download YouTube videos in MP4 or MP3 format
- Support for multiple URLs (up to 5 at once)
- Real-time download status tracking
- Abort functionality for ongoing downloads
- URL counter and validation
- Search engine indexing prevention
- Error logging and notifications

## Requirements

- PHP server (MAMP, XAMPP, or similar)
- Web browser with JavaScript enabled
- Write permissions for temporary file storage

## Installation

1. Clone this repository to your web server directory
2. Ensure the directory has proper write permissions
3. Access the application through your web server

## Security Features

- "For internal use only" disclaimer
- Robots.txt and meta tags to prevent search engine indexing
- Environment-aware server path detection
- Error logging system

## Usage

1. Enter up to 5 YouTube URLs (one per line)
2. Select desired format (MP4 or MP3)
3. Click "Download All" to start the process
4. Monitor download progress in real-time
5. Use the Cancel button to abort ongoing downloads if needed

## Note

This application is intended for internal use only. Please do not share the application URL publicly. 