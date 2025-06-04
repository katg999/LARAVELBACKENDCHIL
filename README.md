# Comprehensive Healthcare & Education Management System

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-2CA5E0?style=for-the-badge&logo=docker&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-316192?style=for-the-badge&logo=postgresql&logoColor=white)

A robust backend system built with Laravel 11, Docker, and PostgreSQL, providing API endpoints for healthcare and education management with integrated Voiceflow conversational AI capabilities.

## 🌟 Key Features

- **Multi-tenant Architecture**: Supports schools, health facilities, and doctors
- **Voiceflow Integration**: Conversational AI endpoints for seamless interactions
- **Comprehensive API**: 50+ endpoints for various healthcare and education operations
- **Filament Admin Panels**: Beautiful dashboards for data management
- **Payment Integration**: Mobile money (MOMO) processing system
- **Document Management**: Secure file uploads and storage
- **OTP Authentication**: Secure verification system for users
- **Appointment Scheduling**: Doctor booking system with meeting links for our different clients Schools, HealthFacilities and Laboratories.

## 📸 Dashboard Previews

### School Management Dashboard
![School Dashboard]( SchoolAPIDashbaord.png "School Display Interface")

### Doctor Administration Portal
![Doctor Dashboard](DoctorAppointments.png "Doctor Management Interface")

## 🚀 Live Deployment

The system is currently deployed on Render:  
[![Render](https://img.shields.io/badge/Render-%46E3B7.svg?style=for-the-badge&logo=render&logoColor=white)](https://laravelbackendchil.onrender.com)


## 🔍 Core API Endpoints

<details>
<summary>📚 Education Management</summary>

- `POST /register-school` - School registration
- `GET /schools` - Retrieve all schools
- `POST /students` - Student creation
- `POST /lab-tests` - Student health tests
</details>

<details>
<summary>🏥 Healthcare Services</summary>

- `POST /register-doctor` - Doctor onboarding
- `POST /appointments` - Appointment booking
- `GET /patients/{healthFacility}` - Patient records
- `POST /maternal-documents` - Pregnancy documentation
</details>

## 🛠️ Technical Stack

- **Framework**: Laravel 11
- **Database**: PostgreSQL
- **Containerization**: Docker
- **Admin Panel**: Filament PHP
- **File Storage**: DigitalOcean Spaces (S3-compatible)
- **CI/CD**: Render automatic deployments

## 📦 Installation

```bash
# Clone the repository
git clone https://github.com/katg999/LARAVELBACKENDCHIL
cd php-laravel-docker

# Copy environment file
cp .env.example .env

# Start containers
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate

# Install dependencies
docker-compose exec app composer install
docker-compose exec app npm install