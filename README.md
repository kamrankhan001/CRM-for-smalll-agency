# 🚀 AgencyCRM

**Open-source CRM for modern agencies** — manage leads, clients, projects, tasks, invoices, and more in one unified system.  
Built with **Laravel 12**, **Vue 3 (Inertia.js)**, and **Tailwind CSS** for speed, scalability, and simplicity.  

> 🧑‍💻 Developed & maintained by [kamrankhan.dev](https://kamrankhan.dev)

![Project Banner](public/screenshot.png)

---

## ✨ Features

### 🧩 Core Modules
- **Leads Management** – Track prospects from first contact to client conversion  
- **Clients** – Manage relationships, documents, and communication history  
- **Projects** – Plan and deliver client work with tasks, budgets, and timelines  
- **Tasks** – Organize team workflow with priorities and due dates  
- **Documents** – Centralized repository for contracts, files, and uploads  
- **Invoices** – Create, send, and monitor payments  
- **Activities** – Full audit log of user actions and system changes  
- **Notifications** – Real-time alerts via Laravel Echo  
- **Notes** – Collaborative documentation system  
- **Users & Roles** – Role-based permissions (Admin, Manager, Member)

---

## 🧠 Database Design

### Entity Relationships
- **Clients (1)** ↔ **(∞) Projects** ↔ **(∞) Tasks**
- **Users (∞)** ↔ **(∞) Projects** (via `project_members`)
- **Leads → Clients** (on conversion)
- **Invoices (1)** ↔ **(1) Clients**

### Key Tables Overview
| Table | Key Fields |
|-------|-------------|
| leads | status, source, score |
| clients | name, industry, revenue |
| projects | name, deadline, budget |
| tasks | title, due_date, progress |
| invoices | number, amount, status |
| documents | name, type, version |
| activities | type, description, changes |
| notifications | type, read_at, recipient_id |

---

## ⚙️ Core Functionality
✅ Role-based access control  
✅ Dashboard analytics & KPIs  
✅ Full CRUD for all modules  
✅ Two-factor authentication  
✅ Dark / Light theme toggle  
✅ Smooth scrolling & responsive design  

---

## 🛠️ Tech Stack

### Backend
- ⚡ **Laravel 12** (PHP framework)
- 🗄️ **MySQL** (Database)
- 🔗 **Inertia.js** (Bridges Laravel + Vue)

### Frontend
- 🧩 **Vue 3** (Reactive UI)
- 🧠 **TypeScript** (Type-safe logic)
- 🎨 **Tailwind CSS** (Utility-first design)
- ⚙️ **Vite** (Fast build tool)

---

## ⚡ Installation

### 1️⃣ Clone Repository
```bash
git clone https://github.com/kamrankhan001/CRM-for-smalll-agency.git
cd CRM-for-smalll-agency
```

### 2️⃣ Install Dependencies
```bash
composer install
npm install
```

### 3️⃣ Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

### 4️⃣ Run Migrations & Seeders
```bash
php artisan migrate --seed
```

### 5️⃣ Start Development Servers
```bash
npm run dev
php artisan serve
```

Then visit: 👉 **http://localhost:8000**

---

## ⚙️ Environment Configuration

Update `.env` with your local setup:
```ini
APP_URL=http://localhost:8000
DB_DATABASE=crm
DB_USERNAME=root
DB_PASSWORD=
```

---

## 📘 User Guide

### 👥 Managing Clients
1. Add clients with detailed contact info  
2. Attach notes, files, and communication logs  
3. Convert leads into active clients

### 🧱 Working with Projects
- Create projects with budgets, timelines, and team members  
- Track task completion and progress  
- Generate invoices from project milestones

### 💰 Invoice Workflow
1. Create invoices with line items  
2. Email invoices directly to clients  
3. Monitor payment status (Paid / Unpaid / Partial)

---

## 🧑‍💻 Developer Notes

### Schema Conventions
- All tables use **UUIDs** as primary keys  
- **Polymorphic** relationships for notes & activities  
- **Soft deletes** enabled globally  
- **Laravel Echo** used for notifications  

### Code Standards
- PSR-12 coding style  
- API resources for all models  
- Vue components are composition-API-based  
- Tailwind with dark mode support  

---

## 🤝 Contributing

Contributions are welcome!  
Follow the GitHub flow:
1. Fork the repository  
2. Create a feature branch  
3. Commit and push your changes  
4. Open a PR with a clear description  

---

## 🪪 License

Released under the [MIT License](https://opensource.org/licenses/MIT).  
Use it freely in commercial or personal projects.

---

### ❤️ Built by [Kamran Khan](https://kamrankhan.dev)
> Empowering small agencies with open-source software.
