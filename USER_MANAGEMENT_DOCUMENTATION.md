## User Management - Super Admin Setup

### Super Admin User Created

- **Email**: superadmin@labsysflow.com
- **Password**: password
- **Role**: Super Admin
- **Permissions**: 29 permissions (all system permissions)

### Features

1. **User Management Page** (`/cp/user-management`)
    - View all users with pagination and search
    - Create new users with roles assignment
    - Edit existing users and update their roles
    - Delete users (except yourself)
    - Manage user status (active/inactive)

2. **Super Admin Capabilities**
    - Can assign any role to users
    - Full access to all permissions
    - Only visible to Super Admin role

3. **Menu Access**
    - Located in sidebar under "Users" menu
    - "User Management" menu item (only visible to Super Admin)

### Files Created/Modified

1. `database/seeders/SuperAdminSeeder.php` - Seeds Super Admin user with all permissions
2. `resources/views/livewire/user-management.blade.php` - User management interface
3. `routes/web.php` - Added user-management route with role middleware
4. `resources/views/components/layouts/app.blade.php` - Added menu item

### How to Use

1. Login with Super Admin credentials:
    - Email: superadmin@labsysflow.com
    - Password: password

2. Navigate to Users > User Management

3. Create new users:
    - Click "Add User" button
    - Fill in name, email, password
    - Select roles (multiple selection supported)
    - Set status (active/inactive)
    - Click "Create"

4. Edit users:
    - Click edit icon on user row
    - Update details and roles
    - Password field optional (leave blank to keep current)

5. Delete users:
    - Click delete icon (red trash icon)
    - Confirm deletion
    - Note: Cannot delete your own account

### Permissions System

The system uses Spatie Laravel-Permission package with 29 default permissions:

- User Management (view, create, update, delete users)
- Role Management (view, create, update, delete, assign roles)
- Permission Management (view, create, update, delete permissions)
- Order Label Management (view, create, update, delete, import, export, print)
- 3PL Management (view, create, update, delete)
- Settings (view, update)
- User Logs (view)
- System (access all features, manage system)

### Security

- Route protected with `role:Super Admin` middleware
- Mount method checks user role
- Users cannot delete themselves
- Enum-based status field for type safety
- Password hashing with bcrypt

### Next Steps

To run the seeder again or for new installations:

```bash
php artisan db:seed --class=SuperAdminSeeder
```

To create additional roles or permissions, use the User Management interface or Artisan tinker.
