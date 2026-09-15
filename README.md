Full-Stack Store and Inventory system 

Frontend: React.js
Backend: Laravel
Database: MySQL



Features
🔐 User Authentication

User Signup

User Login

Token-based Authentication (Laravel Sanctum)

📝 Posts Module

Create Post

Fetch All Posts

Retrieve Posts with like count and “isLiked” status

❤️ Like Module

Like Post

Unlike Post

Real-time UI update

Installation & Running Instructions
Backend Setup (Laravel)

Clone the Repository
git clone YOUR_GITHUB_REPO_LINK
cd backend

composer install

Update your .env file:

DB_DATABASE=your_database
DB_USERNAME=root
DB_PASSWORD=

php artisan key:generate

php artisan migrate

php artisan serve
Your Laravel API will run at: http://127.0.0.1:8000

Frontend Setup (React.js)
Go to Frontend Folder
cd frontend

Install Dependencies

npm install

Start React App
npm run dev
React app will run at: http://localhost:3000
