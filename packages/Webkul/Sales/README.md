# Krayin CRM Sales Component

A comprehensive Sales component for Krayin CRM that provides sales target management, performance tracking, and advanced reporting capabilities.

## Features

### 🎯 Sales Target Management
- **Target Assignment**: Assign targets to individuals, teams, or regions
- **Flexible Timeframes**: Daily, weekly, monthly, quarterly, half-yearly, annual, or custom ranges
- **Dynamic Adjustments**: Modify targets mid-cycle with complete audit trails
- **Notes & Attachments**: Add context and supporting documents to targets

### 📊 Performance Tracking
- **Visual Dashboards**: Interactive charts showing target vs. actual performance
- **Real-time Metrics**: Live tracking of achievement rates, conversion rates, and deal sizes
- **Sparklines**: At-a-glance trend analysis for quick insights
- **Gamification**: Leaderboards with filtering options for teams and individuals
- **Drill-Down**: Click any metric to see underlying deals and data

### 📈 Advanced Reporting
- **Pre-Built Templates**: Commission reports, YoY growth analysis, pipeline health
- **Ad-Hoc Builder**: Drag-and-drop interface for custom report creation
- **Export Options**: CSV, Excel export capabilities
- **Scheduled Reports**: Automated report generation and delivery
- **Sharing**: Public and private report sharing with role-based access

## Installation

1. **Add to Composer Autoload**
   ```json
   "autoload": {
       "psr-4": {
           "Webkul\\Sales\\": "packages/Webkul/Sales/src"
       }
   }
   ```

2. **Register Service Provider**
   Add to `config/app.php`:
   ```php
   Webkul\Sales\Providers\SalesServiceProvider::class,
   ```

3. **Run Migrations**
   ```bash
   php artisan migrate
   ```

4. **Publish Assets**
   ```bash
   php artisan vendor:publish --tag=sales-assets
   ```

## Database Schema

The component includes the following tables:

- `sales_targets` - Core target management
- `sales_target_adjustments` - Audit trail for target changes
- `sales_performance` - Performance metrics and calculations
- `sales_achievements` - Gamification achievements
- `user_achievements` - User achievement tracking
- `sales_reports` - Report definitions and data
- `sales_teams` - Team management
- `sales_team_members` - Team membership
- `sales_regions` - Regional organization

## UI/UX Consistency

### Layout & Design
- **Card-based Dashboard**: Consistent with Krayin's existing design patterns
- **Left Sidebar Navigation**: Seamless integration with current navigation
- **Sticky Filter Bars**: Persistent filtering options matching existing style
- **Blue Accent Theming**: Uses Krayin's primary color palette (`#0E90D9`)

### Mobile Responsive
- **Collapsible Panels**: Optimized for mobile viewing
- **Priority-based Stacking**: Important data shown first on smaller screens
- **Touch-friendly Controls**: Appropriately sized interactive elements

## Permission System

### Role-based Access Control
- **Admin/Manager**: Full access to all features
  - Create, edit, delete targets
  - View all performance data
  - Generate and export reports
  - Manage teams and regions

- **Sales Rep**: Limited to personal data
  - View personal targets and performance
  - Access individual reports
  - Cannot modify targets or view team data

- **Read-Only**: View-only access
  - Finance team access to reports
  - Cannot edit any data
  - Export capabilities for authorized reports

## API Endpoints

### Dashboard
- `GET /admin/sales/dashboard` - Main dashboard
- `GET /admin/sales/dashboard/stats` - Dashboard statistics

### Targets
- `GET /admin/sales/targets` - List targets
- `POST /admin/sales/targets` - Create target
- `PUT /admin/sales/targets/{id}` - Update target
- `DELETE /admin/sales/targets/{id}` - Delete target

### Performance
- `GET /admin/sales/performance` - Performance dashboard
- `GET /admin/sales/performance/stats` - Performance statistics
- `GET /admin/sales/performance/leaderboard` - Leaderboard data

### Reports
- `GET /admin/sales/reports` - List reports
- `POST /admin/sales/reports` - Create report
- `GET /admin/sales/reports/{id}/export` - Export report

## Configuration

### Menu Configuration
The component automatically adds menu items to the admin navigation:

```php
[
    'key' => 'sales',
    'name' => 'Sales',
    'route' => 'admin.sales.dashboard.index',
    'sort' => 3,
    'icon-class' => 'icon-sales',
]
```

### ACL Configuration
Comprehensive permission system with granular access control:

```php
[
    'key' => 'sales.targets.create',
    'name' => 'Create Targets',
    'route' => ['admin.sales.targets.create', 'admin.sales.targets.store'],
]
```

## Customization

### Styling
The component uses Tailwind CSS with custom classes:

```css
.sales-card {
    @apply box-shadow rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900;
}
```

### Vue Components
Modular Vue.js components for interactive features:

- `v-sales-dashboard` - Main dashboard component
- `v-performance-dashboard` - Performance tracking
- `v-sales-target-form` - Target creation/editing
- `v-report-builder` - Custom report builder

## Performance Optimization

### Database Indexing
- Optimized indexes for common queries
- Composite indexes for complex filtering
- Proper foreign key relationships

### Caching Strategy
- Performance metrics cached for 1 hour
- Leaderboard data cached for 30 minutes
- Report data cached until regeneration

### Query Optimization
- Eager loading for related models
- Efficient aggregation queries
- Pagination for large datasets

## Development

### Testing
```bash
# Run component tests
php artisan test packages/Webkul/Sales/tests

# Run specific test suite
php artisan test --testsuite=Sales
```

### Code Style
Follow PSR-12 coding standards and Krayin's conventions:

```bash
# Check code style
./vendor/bin/phpcs packages/Webkul/Sales/src

# Fix code style
./vendor/bin/phpcbf packages/Webkul/Sales/src
```

## Support

For issues, feature requests, or questions:

1. Check the [Krayin CRM Documentation](https://devdocs.krayincrm.com/)
2. Visit the [Krayin CRM Forums](https://forums.krayincrm.com/)
3. Submit issues on the GitHub repository

## License

This component is part of Krayin CRM and is licensed under the [MIT License](LICENSE).

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

Please ensure all tests pass and follow the existing code style.
