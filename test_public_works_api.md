# Public Works API Documentation

## Endpoint
```
GET /api/v1/works-list
```

## Description
Public API endpoint to retrieve published works with advanced filtering, search, sorting, and pagination capabilities. No authentication required.

## Query Parameters

### Pagination
- `page` (integer, default: 1) - Page number
- `per_page` (integer, default: 12, max: 50) - Number of items per page

### Search
- `search` (string) - Search in title, client, and description fields

### Filtering
- `tags` (string or array) - Filter by tags (supports multiple values, comma-separated)
- `categories` (string or array) - Filter by categories (supports multiple values, comma-separated)  
- `category` (string) - Filter by single category (for backward compatibility)
- `year` (integer) - Filter by year
- `client` (string) - Filter by client name (partial match)

### Sorting
- `sort_by` (string, default: 'published_at') - Sort field (published_at, created_at, updated_at, title, client, year, category, display_order)
- `sort_direction` (string, default: 'desc') - Sort direction (asc, desc)

## Example Requests

### Basic Request
```
GET /api/v1/works-list
```

### Search Request
```
GET /api/v1/works-list?search=commercial&page=1&per_page=20
```

### Multiple Tags Filter
```
GET /api/v1/works-list?tags=commercial,advertising&sort_by=year&sort_direction=desc
```

### Multiple Categories Filter
```
GET /api/v1/works-list?categories=color-grading,vfx&year=2024
```

### Complex Filter with Search and Sorting
```
GET /api/v1/works-list?search=nike&tags=commercial&categories=color-grading&sort_by=published_at&sort_direction=desc&per_page=10
```

## Response Structure

```json
{
  "success": true,
  "data": [
    {
      "id": "uuid-string",
      "title": "Work Title",
      "client": "Client Name",
      "category": "color-grading",
      "year": 2024,
      "description": "Work description",
      "slug": "work-slug",
      "hero_banner_image": "image-url",
      "video_project_src": "video-url",
      "video_project_poster": "poster-url",
      "tags": ["commercial", "advertising"],
      "published_at": "2024-01-01T00:00:00.000Z",
      "display_order": 1,
      "credits_count": 5,
      "gallery_items_count": 10
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 12,
    "total": 50,
    "last_page": 5,
    "from": 1,
    "to": 12
  },
  "filters": {
    "categories": ["color-grading", "vfx", "motion-graphics"],
    "tags": ["commercial", "advertising", "narrative"],
    "years": [2024, 2023, 2022],
    "clients": ["Nike", "Apple", "Google"],
    "sort_options": [
      {"value": "published_at", "label": "Published Date"},
      {"value": "title", "label": "Title"},
      {"value": "client", "label": "Client"},
      {"value": "year", "label": "Year"},
      {"value": "category", "label": "Category"}
    ]
  },
  "applied_filters": {
    "search": "nike",
    "tags": ["commercial"],
    "categories": ["color-grading"],
    "category": null,
    "year": null,
    "client": null,
    "sort_by": "published_at",
    "sort_direction": "desc"
  },
  "message": "Works retrieved successfully"
}
```

## Security Features
- Only returns works with status 'published'
- Only returns works with published_at date set
- Validates sort columns against whitelist
- Limits per_page to maximum of 50
- No authentication required for public access
- Comprehensive error handling with fallback responses

## Frontend Integration
This API is designed to be consumed by the frontsite-client React application for displaying the works portfolio without requiring authentication.