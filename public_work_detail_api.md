# Public Work Detail API Documentation

## Endpoint
```
GET /api/v1/works-detail/{id}
```

## Description
Public API endpoint to retrieve detailed information about a specific published work. No authentication required. Only returns works with status 'published' and published_at date set.

## URL Parameters
- `id` (string, required) - The UUID of the work to retrieve

## Example Requests

### Get Work Detail
```
GET /api/v1/works-detail/550e8400-e29b-41d4-a716-446655440000
```

## Response Structure

### Success Response (200)
```json
{
  "success": true,
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "title": "Nike Commercial Campaign",
    "client": "Nike",
    "category": "color-grading",
    "year": 2024,
    "description": "A stunning commercial campaign for Nike featuring advanced color grading techniques.",
    "slug": "nike-commercial-campaign",
    "hero_banner_image": "https://example.com/images/nike-hero.jpg",
    "video_project_src": "https://example.com/videos/nike-project.mp4",
    "video_project_poster": "https://example.com/images/nike-poster.jpg",
    "tags": ["commercial", "advertising", "sports"],
    "published_at": "2024-01-15T10:00:00.000Z",
    "display_order": 1,
    "credits": [
      {
        "id": "credit-uuid-1",
        "role": "Director",
        "name": "John Doe",
        "work_id": "550e8400-e29b-41d4-a716-446655440000"
      },
      {
        "id": "credit-uuid-2",
        "role": "Colorist",
        "name": "Jane Smith",
        "work_id": "550e8400-e29b-41d4-a716-446655440000"
      }
    ],
    "gallery_items": [
      {
        "id": "gallery-uuid-1",
        "type": "image",
        "src": "https://example.com/gallery/image1.jpg",
        "alt": "Behind the scenes shot",
        "caption": "Behind the scenes during filming",
        "display_order": 1,
        "work_id": "550e8400-e29b-41d4-a716-446655440000"
      },
      {
        "id": "gallery-uuid-2",
        "type": "video",
        "src": "https://example.com/gallery/video1.mp4",
        "alt": "Process video",
        "caption": "Color grading process breakdown",
        "display_order": 2,
        "work_id": "550e8400-e29b-41d4-a716-446655440000"
      }
    ],
    "credits_count": 2,
    "gallery_items_count": 2
  },
  "related_works": [
    {
      "id": "related-uuid-1",
      "title": "Adidas Campaign",
      "client": "Adidas",
      "hero_banner_image": "https://example.com/images/adidas-hero.jpg",
      "slug": "adidas-campaign",
      "category": "color-grading"
    },
    {
      "id": "related-uuid-2",
      "title": "Apple Commercial",
      "client": "Apple",
      "hero_banner_image": "https://example.com/images/apple-hero.jpg",
      "slug": "apple-commercial",
      "category": "color-grading"
    }
  ],
  "message": "Work details retrieved successfully"
}
```

### Not Found Response (404)
```json
{
  "success": false,
  "message": "Work not found or not published",
  "data": null,
  "related_works": []
}
```

### Error Response (500)
```json
{
  "success": false,
  "message": "Failed to retrieve work details",
  "data": null,
  "related_works": [],
  "error": "Internal server error"
}
```

## Features

### Security
- **Published Only**: Only returns works with status 'published' and published_at date set
- **No Authentication**: Public access without token requirements
- **Input Validation**: UUID validation for work ID parameter
- **Error Handling**: Comprehensive error handling with appropriate HTTP status codes

### Data Relationships
- **Credits**: Full list of credits associated with the work (director, colorist, etc.)
- **Gallery Items**: Complete gallery with images and videos, including captions and display order
- **Related Works**: Up to 4 related works from the same category (published only)

### Data Formatting
- **Consistent Structure**: All data is formatted consistently for frontend consumption
- **Counts**: Includes credits_count and gallery_items_count for quick reference
- **ISO Dates**: published_at formatted as ISO string for consistent date handling
- **Clean Arrays**: Tags array is always returned (empty array if no tags)

### Related Works Logic
- Same category as the current work
- Published status only
- Excludes the current work being viewed
- Ordered by published_at (newest first)
- Limited to 4 items for performance

## Use Cases
- Work detail pages on frontend
- Portfolio showcase with full project information
- Related works recommendations
- SEO-friendly public content display
- Mobile app integration

## Frontend Integration
This API is designed to be consumed by the frontsite-client React application for displaying detailed work information without requiring authentication. Perfect for public portfolio pages and work showcases.