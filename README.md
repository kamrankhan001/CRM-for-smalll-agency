# Agency CRM

An open-source CRM solution tailored for small agencies, featuring lead/client management, task tracking, and collaborative notes. Built by [kamrankhan.dev](https://kamrankhan.dev).

![Project Banner](public/apple-touch-icon.png)

## Features

### Core Modules
- **Leads Management** - Track potential clients from initial contact to conversion
- **Clients** - Manage client profiles and interaction history
- **Tasks** - Assign and monitor team workflows with deadlines
- **Notes** - Collaborative documentation system
- **Users** - Secure role-based access (Admin/Manager/Member)

### Key Functionality
- Role-based access control system
- Dashboard analytics
- CRUD operations for all core entities
- Two-factor authentication
- Responsive UI with dark/light themes

## Tech Stack

### Backend
- **Laravel 10** - PHP framework
- **MySQL** - Database
- **Inertia.js** - Server-client communication

### Frontend
- **Vue 3** - Reactive components
- **TypeScript** - Type-safe JavaScript
- **Tailwind CSS** - Utility-first styling
- **Vite** - Frontend tooling

## Installation

1. Clone repository:
```bash
git clone https://github.com/kamrankhan001/CRM-for-smalll-agency.git
cd CRM-for-smalll-agency
```

2. Install dependencies:
```bash
composer install
npm install
```

3. Configure environment:
```bash
cp .env.example .env
php artisan key:generate
```

4. Run migrations:
```bash
php artisan migrate --seed
```

5. Start development server:
```bash
npm run dev
```

## Configuration

Set these in `.env`:
```ini
APP_URL=http://localhost:8000
DB_DATABASE=crm
DB_USERNAME=root
DB_PASSWORD=
```

## License
Open-source under [MIT License](LICENSE).

## Contributing
PRs welcome! Follow standard GitHub flow:
1. Fork repo
2. Create feature branch
3. Submit PR with detailed description
