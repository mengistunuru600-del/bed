# Bed Reservation Workflow System

A comprehensive web application for managing bed reservations with a complete approval workflow, suitable for hospitals, hostels, hotels, or dormitories.

## Features

- User registration and login
- Account management (view profile, change password)
- View available beds with detailed information
- View comprehensive bed details including room size and accessories
- Reservation request workflow with multi-level approval
- Payment integration (CBE & TeleBirr)
- Role-based access control (Customer, Receptionist, Manager, Admin)
- Bed management and tracking
- Comprehensive admin panel
- Real-time status updates

## Technologies Used
- PHP
- MySQL
- HTML
- CSS

## Setup Instructions

1. Make sure XAMPP is installed and running (Apache and MySQL).

2. Place this project in `C:\xampp\htdocs\bed\` (or your XAMPP htdocs folder).

3. Open your browser and go to `http://localhost/bed/setup.php` to create the database and tables.

4. Once setup is complete, visit `http://localhost/bed/index.php` to access the dashboard.

## User Roles & Workflow

### 1. Customer
- Register and login to the system
- Submit reservation requests for available beds
- View request status and make payments
- Access personal account management

### 2. Receptionist
- Review customer reservation requests
- Approve or reject requests with notes
- Forward approved requests to manager

### 3. Manager
- Review requests approved by reception
- Final approval or rejection
- Send approved requests to payment stage
### 5. Customer Reservation Process
- Click "Click to Reserve" on any available bed
- Fill comprehensive customer information form:
  - Full name, phone number, location, reason for visit
  - Upload customer picture
  - National ID verification (10-digit validation)
  - Reservation dates (check-in/check-out)
- Submit reservation request
- Receive SMS with generated username and password
- Use credentials to login and track reservation status
### 4. Admin
- Full system administration
- Manage beds, users, and reservations
- Access all features

## Workflow Process

1. **Customer Request**: Customer submits reservation request
2. **Reception Review**: Receptionist approves/rejects request
3. **Manager Approval**: Manager gives final approval
4. **Payment**: Customer completes payment via CBE or TeleBirr
5. **Confirmation**: Reservation is confirmed and bed is marked occupied

## Recent Improvements

- Enhanced bed details page with prominent "Click to Reserve" button
- Added comprehensive bed summary on reservation form
- Improved user interface with better visual hierarchy
- Enhanced reservation workflow with detailed bed information display
- Added room size and accessories preview in bed details
- Improved payment integration with CBE and TeleBirr options
- **NEW:** Advanced reservation form with customer verification
- **NEW:** National ID verification with picture display
- **NEW:** SMS code generation for account access
- **NEW:** File upload for customer pictures
- **NEW:** Comprehensive customer information collection

## Features

- Multi-role user authentication
- Reservation request workflow
- Payment integration (CBE & TeleBirr)
- Bed management and tracking
- Comprehensive admin panel
- Real-time status updates
- Account management

## Database Structure

- **users**: id, username, email, password, role, full_name, phone, created_at
- **beds**: id, name, status
- **reservations**: id, bed_id, guest_name, check_in, check_out
- **reservation_requests**: id, customer_id, bed_id, check_in, check_out, status, receptionist_id, manager_id, notes, payment_info

## Default Accounts

- **Admin**: username: `admin`, password: `admin123`
- **Manager**: username: `manager`, password: `manager123`
- **Receptionist**: username: `reception`, password: `reception123`

## Payment Integration

The system includes mock payment integration for:
- **Commercial Bank of Ethiopia (CBE)**: Account transfers
- **TeleBirr**: Mobile money payments

## Usage

1. **Customers**: Register → Login → Request Reservation → Wait for approval → Make payment → Get confirmation
2. **Receptionists**: Login → Review pending requests → Approve/Reject with notes
3. **Managers**: Login → Review approved requests → Final approval → Send to payment
4. **Admins**: Full access to all system management features

The workflow ensures proper oversight and approval processes while providing customers with a smooth reservation experience.