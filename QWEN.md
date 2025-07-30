# QWEN.md

This file provides guidance to Qwen Code when working with code in this repository.

## Development Commands

### Frontend (Vite + Vue)
- `npm run dev` - Start Vite development server
- `npm run build` - Build for production
- `npm run build:ssr` - Build with SSR support
- `npm run lint` - Run ESLint with auto-fix
- `npm run format` - Format code with Prettier
- `npm run format:check` - Check formatting without changes

### Backend (Laravel)
- `composer dev` - Start full development environment (Laravel server, queue worker, logs, and Vite)
- `composer dev:ssr` - Start development with SSR
- `php artisan serve` - Start Laravel development server only
- `php artisan migrate` - Run database migrations
- `php artisan queue:work` - Process queues
- `php artisan pail` - View logs in real-time

## Architecture Overview

This is a Laravel + Vue.js (Inertia.js) finance management application.

### Backend Stack
- **Framework**: Laravel 12 with PHP 8.2+
- **Database**: MySQL
- **Queue System**: Laravel queues for background processing
- **PDF Processing**: Spatie PDF-to-text for document parsing
- **AI Integration**: OpenAI Laravel package for transaction parsing
- **Currency**: Laravel Currency Converter for multi-currency support

### Frontend Stack  
- **Framework**: Vue 3 with TypeScript and Composition API
- **Build Tool**: Vite with Laravel integration
- **Styling**: Tailwind CSS 4.x with custom components
- **UI Components**: shadcn/vue as main component library + Reka UI (headless components)
- **Charts**: Chart.js with Vue wrapper and Unovis for advanced visualizations
- **Routing**: Inertia.js for SPA-like experience

### Core Models & Relationships
- **User**: Authentication and user management
- **Transaction**: Financial transactions with categories, amounts, dates
- **Category**: Transaction categorization system
- **Relationships**: User hasMany Transactions, Transaction belongsTo Category

### Key Features
- **PDF Upload & Parsing**: Upload financial documents, extract transaction data using AI
- **Transaction Management**: View, categorize, and manage financial transactions  
- **Dashboard**: Overview with charts and financial summaries
- **AI Advisor**: Financial insights and recommendations
- **Multi-currency Support**: Handle different currencies with conversion
- **Real-time Processing**: Background queue processing for heavy operations

### File Structure
- `app/` - Laravel application logic (Controllers, Models, Services)
- `resources/js/` - Vue.js frontend application
  - `components/ui/` - shadcn/vue UI component library
  - `pages/` - Inertia.js page components
  - `layouts/` - Application layout components
  - `composables/` - Vue composition functions
- `database/migrations/` - Database schema definitions
- `routes/` - Application routing (web.php, auth.php, settings.php)

### Important Services
- `OpenAiTransactionParserService`: AI-powered transaction parsing from documents
- `PdfToTextService`: Convert PDF files to text for processing
- `ParseFinancialDocumentAction`: Main action for processing uploaded financial documents

### Development Notes
- Uses Inertia.js for seamless Laravel-Vue integration
- Frontend components follow shadcn/vue design system patterns
- Database uses MySQL for all environments
- Queue processing required for PDF parsing and AI operations
- TypeScript strict mode enabled for frontend development