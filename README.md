# 🏥 SilverCare - SMART COMPANION FOR SENIORS

**A comprehensive healthcare management ecosystem designed to bridge the gap between independent elderly living and proactive caregiving.**

SilverCare is an advanced web application engineered with **Laravel 11, PHP, PostgreSQL, JavaScript, and Tailwind CSS**. It addresses the critical challenge of managing complex health regimens for the aging population by unifying medication adherence, vital monitoring, caregiver oversight, and wellness tracking into a single, synchronized platform. It also provides caregivers with real-time insights and management tools to ensure the well-being of their loved ones. 

### 🎯 Project Vision
**The Problem:**
As the global population ages, the disconnect between elderly individuals managing their daily health and the caregivers responsible for them grows. Medication non-adherence, silent health deterioration, and social isolation are critical risks that often go unnoticed until an emergency occurs.

**The Solution:**
SilverCare solves this by providing a "Command Center" for care. It empowers elderly users to maintain their autonomy through simplified, accessible tools while giving family members and professional caregivers real-time visibility into health trends and daily activities. This proactive approach transforms care from reactive emergency handling to continuous, preventive monitoring.

### 👥 Target Audience
- **Elderly Individuals**: Seniors seeking to age in place with confidence, using intuitive tools to track their own wellness without overwhelming complexity.
- **Family Caregivers**: Adult children or spouses who need peace of mind and data-driven insights to advocate for their loved ones during medical visits.
- **Professional Care Providers**: Home health aides requiring a centralized dashboard to efficiently monitor compliance and vitals for multiple clients simultaneously.

---

## 📋 Table of Contents
- [Key Features](#-key-features)
- [System Architecture](#-system-architecture)
- [Technology Stack](#-technology-stack)
- [Getting Started](#-getting-started)
- [Database Schema](#-database-schema)
- [License](#-license)

---

## 🌟 Key Features

### 💊 Advanced Medication Management
The core of SilverCare is its sophisticated medication adherence engine.
- **Flexible Scheduling**: Supports complex schedules (daily, specific days of the week, intervals).
- **Adherence Tracking**: Logs `taken`, `missed`, and `skipped` doses with precise timestamps.
- **Smart Reminders**: Automated notifications for upcoming and missed doses to ensure compliance.
- **Safety**: Built-in conflict detection (foundation laid for drug-drug interaction checks).

### 📊 Holistic Health Monitoring
Goes beyond basic vitals to provide a complete picture of user health.
- **Nine Vital Metrics**: Tracks Heart Rate, Blood Pressure, Blood Sugar, Temperature, Weight, Oxygen Saturation, Respiratory Rate, Cholesterol, and Sleeping Hours.
- **Visual Analytics**: Interactive Chart.js visualizations for spotting trends over time.
- **Report Generation**: One-click PDF export of health history for medical appointments.
- **Mood Tracking**: daily wellness check-ins to monitor mental health alongside physical stats.

### 👩‍⚕️ Caregiver Command Center
A dedicated dashboard for professional or family caregivers.
- **One-to-Many Monitoring**: Manage multiple elderly profiles from a single interface.
- **Real-Time Alerts**: Receive immediate notifications for critical health thresholds or missed medications.
- **Intervention Tools**: Ability to adjust medication schedules and task lists remotely.
- **Activity Feed**: A chronological log of the patient's interactions and health events.

### 🧘 Wellness & Cognitive Health
Features designed to keep the mind and body active.
- **Interactive Modules**: Breathing exercises, Memory Match games, and Morning Stretches.
- **Daily Engagement**: "Word of the Day" and customizable daily checklists for routine building.

### 🔗 Integrated Ecosystem
- **Google Fit Synchronization**: Seamlessly pulls activity data (Steps, Calories, Distance) via OAuth 2.0.
- **Secure Authentication**: Robust role-based access control (RBAC) protecting sensitive medical data.

---

## 🏗️ System Architecture

SilverCare follows a **Service-Oriented Architecture (SOA)** pattern within Laravel to ensure scalability and maintainability.

- **Controllers**: Lean controllers that handle HTTP requests and responses.
- **Services Layer**: Complex business logic is encapsulated in dedicated service classes (`MedicationService`, `HealthMetricService`, `GoogleFitService`), promoting code reuse and testability.
- **Repository Pattern**: Abstracted database access for core models.
- **Event-Driven**: Uses Laravel Events and Listeners for decoupled actions (e.g., sending notifications when a vital is critical).

---

## 🛠️ Technology Stack

| Component | Technology | Description |
|-----------|------------|-------------|
| **Backend Framework** | Laravel 11 | Robust PHP framework for secure API and web routes. |
| **Language** | PHP 8.2+ | Server-side logic. |
| **Database** | PostgreSQL 17 | Reliable relational database for complex health data. |
| **Frontend** | Blade + Tailwind CSS | Responsive, accessible UI components. |
| **JavaScript** | Alpine.js / Vanilla JS | Lightweight interactivity and AJAX handling. |
| **Charts** | Chart.js 4.0 | Data visualization for health metrics. |
| **Authentication** | Laravel Breeze | Secure session handling and RBAC. |
| **Integrations** | Google Fit API | External health data synchronization. |
| **PDF Generation** | DomPDF | Report exports. |

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js & NPM
- PostgreSQL 17

### Installation

1. **Clone the Repository**
   ```bash
   git clone https://github.com/yourusername/silvercare-web.git
   cd silvercare-web
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Edit `.env` to configure your PostgreSQL database credentials and Google Fit API keys.*

4. **Database Setup**
   ```bash
   php artisan migrate --seed
   ```

5. **Run the Application**
   ```bash
   # Terminal 1: Start Laravel Server
   php artisan serve

   # Terminal 2: Compile Assets
   npm run dev
   ```

   Visit `http://localhost:8000` to access the application.

---

## 🗃️ Database Schema Overview

The database is normalized to support scalable health tracking:

- **`users` & `user_profiles`**: Handles Identity and extended profile attributes.
- **`caregiver_elderly`**: Pivot table managing the care relationships.
- **`medications` & `medication_logs`**: Stores schedules and execution history.
- **`health_metrics`**: Polymorphic-style table (using type columns) for all vital signs.
- **`google_fit_tokens`**: Encrypted storage for OAuth tokens.

---

## 📄 License

FINAL PROJECT IN IT 314 - Web Systems and Technologies
