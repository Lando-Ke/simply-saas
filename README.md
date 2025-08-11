# Simply SaaS - Multi-Tenant Project Management Platform

Simply SaaS is a comprehensive Software as a Service (SaaS) application built with Laravel and Filament, designed to provide multi-tenant project and task management with role-based access control, subscription management, and customizable branding.


## 🏗️ Architecture Overview

### **Role Hierarchy**
```
Super Admin (100) → App Admin (90) → Tenant Admin (85) → Admin (80) → Manager (60) → Team Lead (40) → User (20) → Client (10)
```

### **Filament Access Matrix**
| Role | Filament Access | Scope | Features Available |
|------|-----------------|-------|-------------------|
| **Super Admin** | ✅ Full Access | All Tenants | Global stats, All tenant management, User management |
| **App Admin** | ✅ Full Access | All Tenants | Global stats, Tenant management, User management |
| **Tenant Admin** | ✅ Limited Access | Own Tenant Only | Tenant stats, Branding, Billing, Own tenant users |
| **Admin** | ❌ No Access | N/A | Uses main application interface |
| **Manager** | ❌ No Access | N/A | Uses main application interface |
| **User** | ❌ No Access | N/A | Uses main application interface |

## 🚀 Installation & Setup

### Prerequisites

- **PHP** 8.1 or higher
- **Composer** 2.0+
- **Node.js** 18+ and npm
- **MySQL** 8.0+ or **PostgreSQL** 13+
- **Git**

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/simply-saas.git
cd simply-saas
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### 3. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Database Configuration

Edit your `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simply_saas
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Optional: Configure mail settings for notifications
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

### 5. Database Setup

```bash
# Run migrations
php artisan migrate

# Seed the database with comprehensive test data
php artisan db:seed

# This creates:
# - Role and permission structure
# - Test users for all roles
# - Sample tenants with different subscription levels
# - Test subscriptions and plans
# - Tenant branding configurations
```

### 6. Build Assets

```bash
# Compile assets for development
npm run dev

# OR compile for production
npm run build
```

### 7. Start the Application

```bash
# Start the development server
php artisan serve

# The application will be available at:
# Main App: http://localhost:8000
# Filament Admin: http://localhost:8000/admin
```

## 👥 User Roles & Test Credentials

### **Filament Dashboard Access** (Can access `/admin`)

| Role | Email | Name | Password | Tenant Assignment | Dashboard Access |
|------|-------|------|----------|------------------|-----------------|
| **Super Admin** | `superadmin@example.com` | Super Administrator | `password` | All Tenants | Global statistics, all tenant management |
| **App Admin** | `appadmin@example.com` | Application Administrator | `password` | All Tenants | Global statistics, tenant/user management |
| **Tenant Admin** | `tenantadmin1@acmecorp.com` | John Tenant Admin | `password` | Acme Corp | Tenant-specific stats, branding, billing |
| **Tenant Admin** | `tenantadmin2@techstart.com` | Jane Tenant Admin | `password` | TechStart Inc | Tenant-specific stats, branding, billing |

### **Regular Application Users** (Cannot access Filament)

| Role | Email | Name | Password | Tenant Assignment | Main App Access |
|------|-------|------|----------|------------------|----------------|
| **Admin** | `admin@example.com` | Regular Administrator | `password` | Creative Solutions | Projects, tasks, team management |
| **Manager** | `manager@acmecorp.com` | Project Manager | `password` | Acme Corp | Project oversight, task assignment |
| **User** | `user@techstart.com` | Regular User | `password` | TechStart Inc | Task execution, time tracking |

### **Test Tenants**

| Tenant Name | ID | Primary Admin | Subscription Level | Status |
|-------------|----|--------------|--------------------|--------|
| **Acme Corporation** | `acme-corp` | tenantadmin1@acmecorp.com | Premium ($79.99/month) | Active |
| **TechStart Inc** | `techstart-inc` | tenantadmin2@techstart.com | Basic ($29.99/month) | Active |
| **Creative Solutions** | `creative-solutions` | admin@example.com | Free (Trial) | Trial (14 days remaining) |

## 🧪 Testing User Roles & Access Control

### **Test 1: Super Admin Access**
```bash
# Login Credentials
Email: superadmin@example.com
Password: password

# Expected Access:
✅ Can access /admin
✅ See global statistics (all tenants)
✅ Manage all tenants
✅ View revenue across all subscriptions
✅ Access all administrative features
```

### **Test 2: App Admin Access**
```bash
# Login Credentials
Email: appadmin@example.com
Password: password

# Expected Access:
✅ Can access /admin
✅ See global statistics
✅ Manage tenants and users
✅ Cannot access tenant-specific branding/billing
✅ View system-wide analytics
```

### **Test 3: Tenant Admin Access**
```bash
# Login Credentials (Acme Corp)
Email: tenantadmin1@acmecorp.com
Password: password

# Expected Access:
✅ Can access /admin/acme-corp
✅ See Acme Corp specific statistics only
✅ Manage Acme Corp branding (logo, colors)
✅ Manage Acme Corp subscriptions
✅ View only Acme Corp users
❌ Cannot see other tenants' data
```

### **Test 4: Regular Admin Blocked Access**
```bash
# Login Credentials
Email: admin@example.com
Password: password

# Expected Behavior:
❌ Cannot access /admin (receives 403 error)
✅ Can access main application (/dashboard)
✅ Can manage projects and tasks
✅ Standard application functionality works
```

### **Test 5: Manager/User Blocked Access**
```bash
# Login Credentials
Email: manager@acmecorp.com
Password: password

# Expected Behavior:
❌ Cannot access /admin
✅ Can access /dashboard, /projects, /tasks
✅ Role-appropriate features in main app
```

## 📊 Dashboard Features Testing

### **Super Admin Dashboard** (`/admin` as superadmin@example.com)

**Statistics Widgets:**
- **Total Tenants**: Shows count of all registered tenants (3)
- **Total Users**: Shows count of all users across all tenants (10)
- **Active Subscriptions**: Shows active subscriptions across all tenants (5)
- **Revenue This Month**: Shows total MRR across all tenants ($139.97)

**Interactive Charts:**
- **Subscription Trends**: Line chart showing subscription creation over time
- **Revenue Chart**: Dual-axis showing revenue growth and new user acquisition
- **Filters**: Test all time ranges (7 days, 30 days, 3 months, 6 months, 1 year)

### **Tenant Admin Dashboard** (`/admin/acme-corp` as tenantadmin1@acmecorp.com)

**Statistics Widgets:**
- **Tenant Users**: Shows users in Acme Corp only (2)
- **Active Subscriptions**: Shows Acme Corp subscriptions only (2)
- **Monthly Spend**: Shows Acme Corp spending ($109.98)
- **Tenant Status**: Shows "Active" status

**Management Features:**
- **Branding**: Upload logo, change primary color (#1f2937), secondary color (#6b7280)
- **Billing**: View/manage Premium subscription, change plans, view payment history

## 🎨 Feature Highlights

### **Advanced Branding Management**
```bash
# Access: /admin/{tenant}/brandings as Tenant Admin
Features:
- Logo upload with image editing
- Color picker for primary/secondary colors
- Organization name and tagline management
- Website URL configuration
- Live preview of changes
```

### **Comprehensive Billing Management**
```bash
# Access: /admin/{tenant}/billings as Tenant Admin
Features:
- View current subscription status
- Change subscription plans
- Cancel/reactivate subscriptions
- View payment history
- Monitor billing cycles
- Track subscription metrics
```

### **Interactive Analytics**
```bash
# Available to all Filament users with role-appropriate data
Features:
- Real-time subscription trends
- Revenue/spending analytics with dual-axis charts
- User growth tracking
- Filterable date ranges
- Export capabilities
- Responsive chart design
```

## 🧪 Automated Testing

### **Run Access Control Tests**
```bash
# Test all access control scenarios
php artisan test --filter FilamentAccessControlTest

# Test widget functionality
php artisan test --filter FilamentWidgetTest

# Run all tests
php artisan test
```

### **Test Coverage Includes:**
- ✅ Role-based Filament access control
- ✅ Tenant data isolation
- ✅ Widget data filtering by role
- ✅ User role hierarchy validation
- ✅ Branding resource permissions
- ✅ Billing resource access control
- ✅ Chart data accuracy and filtering

## 🛠️ Creating New Admin Users

### **Method 1: Using the Provided Script**
```bash
php create_app_admin.php

# Follow prompts to enter:
# - Name: Your Admin Name
# - Email: admin@yourcompany.com
# - Password: (leave empty for 'password')
```

### **Method 2: Using Artisan Tinker**
```bash
php artisan tinker

# Create App Admin (can manage all tenants)
$user = App\Models\User::create([
    'name' => 'New App Admin',
    'email' => 'newappadmin@example.com',
    'password' => Hash::make('securepassword'),
    'email_verified_at' => now(),
    'hourly_rate' => 100.00,
]);
$user->assignRole('app-admin');

# Create Tenant Admin (limited to specific tenant)
$user = App\Models\User::create([
    'name' => 'New Tenant Admin',
    'email' => 'newtenantadmin@company.com',
    'password' => Hash::make('securepassword'),
    'email_verified_at' => now(),
    'hourly_rate' => 75.00,
]);
$user->assignRole('tenant-admin');

# Assign to specific tenant
$tenant = App\Models\Tenant::find('your-tenant-id');
$tenant->users()->attach($user->id, [
    'role' => 'admin',
    'joined_at' => now()
]);
```

## 🐛 Troubleshooting

### **Common Issues & Solutions**

#### **Issue: 403 Access Denied to Filament**
```bash
# Check user roles
php artisan tinker
>>> $user = App\Models\User::where('email', 'your-email@example.com')->first();
>>> $user->getRoleNames();

# If user has no roles, assign appropriate role:
>>> $user->assignRole('tenant-admin'); // or 'app-admin'
```

#### **Issue: Tenant Not Showing in Dropdown**
```bash
# Verify tenant assignment
>>> $user = App\Models\User::find(1);
>>> $user->tenants; // Should show associated tenants

# Assign user to tenant if missing:
>>> $tenant = App\Models\Tenant::find('tenant-id');
>>> $tenant->users()->attach($user->id, ['role' => 'admin', 'joined_at' => now()]);
```

#### **Issue: Charts Not Loading**
```bash
# Ensure you have test data
php artisan db:seed --class=AccessControlTestSeeder

# Check subscription data exists
>>> App\Models\Subscription::count(); // Should be > 0
```

#### **Issue: Styling Not Applied**
```bash
# Rebuild assets
npm run build

# Clear Laravel caches
php artisan config:clear
php artisan view:clear
php artisan cache:clear
```

---