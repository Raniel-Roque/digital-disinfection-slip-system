# Digital Disinfection Slip System

A web-based system for managing truck disinfection slips with role-based access control.

## Features

### Super Admin
- **Data Management**: Manage Guards (including Super Guards), Admins, Vehicles, Locations, Reasons, and Drivers
  - Create, update, delete, restore, disable, and enable records
  - Reset user passwords
- **Slip Management**: View and manage all disinfection slips
- **Settings**: Customize system settings
- **Audit Trail**: Track all system activities
- **Dashboard**: View statistics and system overview
- **Issues**: View and resolve submitted issues
- **Export Data**: Export data to CSV files or print lists

### Admin
- **Data Management**: Manage Guards (including Super Guards), Vehicles, Locations, Reasons, and Drivers
  - Create, update, disable, and enable records
  - Reset user passwords
- **Slip Management**: View and manage all disinfection slips
- **Audit Trail**: View system activity logs
- **Dashboard**: View statistics and system overview
- **Issues**: View and resolve submitted issues

### Super Guard
- **Data Management**: Manage Guards, Vehicles, Locations, Reasons, and Drivers
  - Create, update, disable, and enable records
  - Reset user passwords
- **Slip Management**: Manage slips for their assigned location
- **Guard Functions**: All standard guard features plus limited administrative privileges

### Guard
- **Create Slips**: Create disinfection slips for allowed locations
- **Complete Slips**: Complete incoming slips that are in-transit
- **Sumbit Issues**: Submit problems or issues with slips

## Installation

Follow these steps to set up the system:

1. Install Node.js dependencies:
   ```bash
   npm install
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Copy environment file:
   ```bash
   cp .env.example .env
   ```

4. Generate application key:
   ```bash
   php artisan key:generate
   ```

5. Create storage symlink:
   ```bash
   php artisan storage:link
   ```

6. (Optional) Edit the database seeder file (`database/seeders/DatabaseSeeder.php`) to customize initial data as needed.

7. Set up database:
   ```bash
   php artisan db:setup --seed
   ```

## System Requirements

- PHP 8.1 or higher
- Composer
- Node.js and npm
- MySQL/MariaDB or PostgreSQL database
- Web server (Apache/Nginx) or PHP built-in server

## Technology Stack

- **Framework**: Laravel with Livewire for real-time interactions
- **Features**: Location-based login, audit trail logging, soft deletes, file attachments
