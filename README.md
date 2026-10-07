# Ghion Travel: Online Tour & Travel Guide System

Senior project for the BSc in Computer Science at St. Mary's University (Addis Ababa).

A booking website for travel in Ethiopia. Visitors can browse and book tour packages, hotels and rental cars, and admins manage everything from a dashboard.

## Features

**For travelers**
- Browse tour packages, hotels and cars, each with a details page
- Book packages and cars, with booking confirmation emails
- Account registration, login, and password reset by email
- "My bookings" page
- Map view of destinations
- Telebirr and CBE payment QR codes

**For admins**
- Dashboard with booking analytics
- Add, edit and view packages, hotels and cars
- Manage bookings, users and contact messages
- Notifications and site settings

## Tech stack

- PHP 8 (no framework)
- MongoDB with the official `mongodb/mongodb` driver
- PHPMailer over Gmail SMTP for transactional email
- HTML, CSS and JavaScript

## Running locally

1. Install PHP 8 with the MongoDB extension, Composer, and MongoDB.
2. Install dependencies:
   ```bash
   composer install
   ```
3. Copy `.env.example` to `.env` and fill in your SMTP details (a Gmail app password works). Export them as environment variables, or set them in your PHP server config.
4. Start MongoDB on `localhost:27017`. The app uses a database called `ghion_travel`.
5. Seed sample data by opening `setup_packages.php` and `setup_bookings.php` once.
6. Start the server:
   ```bash
   php -S localhost:8000
   ```
7. Open http://localhost:8000.

The login page lists demo accounts for local testing only.

## What I'd improve next

- Move to a framework (Laravel) with routing and templates instead of one file per page
- Hash-check and rate-limit logins, and add CSRF tokens to forms
- Automated tests for the booking flow
- Deploy a public demo
