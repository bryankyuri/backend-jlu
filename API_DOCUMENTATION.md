# Laravel Backend API Setup

## 🚀 **Laravel Backend Successfully Created!**

### **Project Details:**
- **Version**: Laravel 12.x (matching your hosting)
- **Location**: `laravel-backend/`
- **API Base URL**: `https://staging-api.parallelstudio.asia/api/v1`

---

## 📋 **Available API Endpoints:**

### **Projects API:**
```
GET    /api/v1/projects              - Get all projects
GET    /api/v1/projects/{id}         - Get single project
GET    /api/v1/projects/category/{category} - Get projects by category
```

### **Team API:**
```
GET    /api/v1/team                  - Get all team members
GET    /api/v1/team/{id}             - Get single team member
```

### **Contact API:**
```
POST   /api/v1/contact               - Submit contact form
```

### **Utilities:**
```
GET    /api/v1/services              - Get available services/categories
GET    /api/health                   - Health check endpoint
```

---

## 🔧 **React Integration:**

### **Update your React API configuration:**

In your React projects (`frontsite-client` and `cms-front`), update your API base URL:

```javascript
// src/api/index.js
const BASE_URL = process.env.NODE_ENV === 'production' 
  ? 'https://staging-api.parallelstudio.asia/api/v1'
  : 'http://localhost:8000/api/v1';

export const fetchData = async (endpoint) => {
  try {
    const response = await fetch(`${BASE_URL}${endpoint}`, {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    });
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    return await response.json();
  } catch (error) {
    console.error('API Error:', error);
    throw error;
  }
};

// Example usage:
export const getProjects = () => fetchData('/projects');
export const getTeam = () => fetchData('/team');
export const submitContact = (data) => 
  fetch(`${BASE_URL}/contact`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: JSON.stringify(data),
  });
```

---

## 🗄️ **Database Setup (When Ready):**

1. **Create database tables:**
   ```php
   php artisan make:migration create_projects_table
   php artisan make:migration create_team_members_table
   php artisan make:migration create_contacts_table
   ```

2. **Run migrations:**
   ```bash
   php artisan migrate
   ```

---

## 📤 **Deployment to Hosting:**

1. **Upload Laravel files** to your hosting (except `public/` folder)
2. **Upload `public/` contents** to your web root
3. **Update `.env`** with production database credentials
4. **Set file permissions** (755 for folders, 644 for files)
5. **Run composer install** if needed

---

## ✅ **CORS Configuration:**
- ✅ Sanctum installed and configured
- ✅ CORS domains set for your React apps
- ✅ API middleware properly configured

---

## 🧪 **Test Your API:**

### **Health Check:**
```bash
curl https://staging-api.parallelstudio.asia/api/health
```

### **Get Projects:**
```bash
curl https://staging-api.parallelstudio.asia/api/v1/projects
```

---

## 📝 **Next Steps:**

1. Deploy Laravel to your hosting
2. Update React apps to use new API endpoints
3. Test all API endpoints
4. Set up database and migrate from static data
5. Configure email for contact form

Your Laravel backend is now ready to serve your React applications! 🎉