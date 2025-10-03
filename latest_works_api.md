# Latest Works Public API Documentation

## Endpoint
```
GET /api/v1/works-latest
```

## Description
Public API endpoint to retrieve the latest published works sorted by year (newest first) and then by updated_at (most recently updated first). No authentication required. Only returns works with status 'published' and published_at date set.

## Query Parameters

### Pagination
- `page` (integer, default: 1) - Page number
- `per_page` (integer, default: 12, max: 50) - Number of items per page

## Sorting Logic
1. **Primary Sort**: Year (descending) - Shows works from the newest year first
2. **Secondary Sort**: Updated At (descending) - Among works from the same year, shows most recently updated first

This ensures that:
- 2024 works appear before 2023 works
- Within 2024 works, recently updated projects appear first
- Perfect for showcasing the latest and freshest content

## Example Requests

### Basic Request
```
GET /api/v1/works-latest
```

### With Pagination
```
GET /api/v1/works-latest?page=1&per_page=20
```

### Get First 6 Latest Works
```
GET /api/v1/works-latest?per_page=6
```

## Response Structure

### Success Response (200)
```json
{
  "success": true,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "title": "Nike 2024 Campaign",
      "client": "Nike",
      "category": "color-grading",
      "year": 2024,
      "description": "Latest Nike commercial with cutting-edge color grading.",
      "slug": "nike-2024-campaign",
      "hero_banner_image": "https://example.com/images/nike-2024-hero.jpg",
      "video_project_src": "https://example.com/videos/nike-2024.mp4",
      "video_project_poster": "https://example.com/images/nike-2024-poster.jpg",
      "tags": ["commercial", "advertising", "sports"],
      "published_at": "2024-03-15T10:00:00.000Z",
      "updated_at": "2024-03-20T14:30:00.000Z",
      "display_order": 1,
      "credits_count": 5,
      "gallery_items_count": 12
    },
    {
      "id": "550e8400-e29b-41d4-a716-446655440001",
      "title": "Apple Vision Pro Launch",
      "client": "Apple",
      "category": "vfx",
      "year": 2024,
      "description": "VFX work for Apple Vision Pro launch campaign.",
      "slug": "apple-vision-pro-launch",
      "hero_banner_image": "https://example.com/images/apple-vision-hero.jpg",
      "video_project_src": "https://example.com/videos/apple-vision.mp4",
      "video_project_poster": "https://example.com/images/apple-vision-poster.jpg",
      "tags": ["tech", "vfx", "product-launch"],
      "published_at": "2024-02-01T09:00:00.000Z",
      "updated_at": "2024-03-18T11:15:00.000Z",
      "display_order": 2,
      "credits_count": 8,
      "gallery_items_count": 15
    },
    {
      "id": "550e8400-e29b-41d4-a716-446655440002",
      "title": "Mercedes EQS Campaign",
      "client": "Mercedes-Benz",
      "category": "color-grading",
      "year": 2023,
      "description": "Luxury automotive commercial with premium color treatment.",
      "slug": "mercedes-eqs-campaign",
      "hero_banner_image": "https://example.com/images/mercedes-hero.jpg",
      "video_project_src": "https://example.com/videos/mercedes-eqs.mp4",
      "video_project_poster": "https://example.com/images/mercedes-poster.jpg",
      "tags": ["automotive", "luxury", "commercial"],
      "published_at": "2023-12-10T16:00:00.000Z",
      "updated_at": "2024-01-05T10:20:00.000Z",
      "display_order": 3,
      "credits_count": 6,
      "gallery_items_count": 18
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 12,
    "total": 45,
    "last_page": 4,
    "from": 1,
    "to": 12
  },
  "sorting": {
    "primary": "year (desc)",
    "secondary": "updated_at (desc)",
    "description": "Latest works sorted by newest year first, then by most recently updated"
  },
  "message": "Latest works retrieved successfully"
}
```

### Error Response (500)
```json
{
  "success": false,
  "message": "Failed to retrieve latest works",
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 12,
    "total": 0,
    "last_page": 1
  }
}
```

## Features

### Security
- **Published Only**: Only returns works with status 'published' and published_at date set
- **No Authentication**: Public access without token requirements
- **Input Validation**: Page and per_page parameter validation
- **Error Handling**: Comprehensive error handling with appropriate HTTP status codes

### Data Structure
- **Complete Work Information**: All essential work fields including media URLs and metadata
- **Relationship Counts**: Credits and gallery items counts for quick reference
- **Formatted Dates**: ISO formatted published_at and updated_at timestamps
- **Tags Array**: Always returns an array (empty if no tags)

### Sorting Benefits
- **Year Priority**: Ensures newest year projects are always shown first
- **Freshness**: Recently updated content within the same year appears first
- **Consistency**: Predictable ordering for frontend caching and display
- **Performance**: Optimized database query with proper indexing

### Use Cases
- **Homepage Latest Works Section**: Perfect for showing newest projects
- **Portfolio Recent Updates**: Highlight recently updated or newest projects
- **Mobile App Feed**: Clean, sorted feed of latest content
- **SEO Fresh Content**: Search engines prefer recently updated content
- **Client Showcases**: Display the most current and relevant work

## Frontend Integration Examples

### React Hook Usage
```javascript
// Get latest 6 works for homepage
const { data: latestWorks } = useQuery(
  'latest-works',
  () => fetchLatestWorks({ per_page: 6 })
);

// Infinite scroll for latest works
const { data: allLatestWorks } = useInfiniteQuery(
  'latest-works-infinite',
  ({ pageParam = 1 }) => fetchLatestWorks({ page: pageParam })
);
```

### API Service Usage
```javascript
// Basic usage
const latestWorks = await apiService.getLatestWorks();

// With pagination
const latestWorks = await apiService.getLatestWorks({ 
  page: 2, 
  per_page: 20 
});
```

This API is perfect for displaying the freshest and most relevant content to users, ensuring they always see the latest work and recent updates first.