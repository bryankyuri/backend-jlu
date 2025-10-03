# Related Works Public API Documentation

## Endpoint
```
GET /api/v1/works-related/{id}
```

## Description
Public API endpoint to retrieve related works based on a reference work's UUID. Finds published works that share tags or category with the specified work using an intelligent relevance scoring system. No authentication required.

## URL Parameters
- `id` (string, required) - The UUID of the reference work to find related works for

## Query Parameters
- `limit` (integer, default: 8, max: 20) - Maximum number of related works to return
- `sort_by` (string, default: 'relevance') - Sorting method:
  - `relevance` - Sort by relevance score (category match + shared tags)
  - `latest` - Sort by published_at (newest first)
  - `year` - Sort by year then published_at (newest first)

## Relevance Scoring System

The API uses an intelligent scoring system to rank related works:

### Scoring Logic
- **Category Match**: +10 points for same category
- **Tag Matches**: +1 point per shared tag
- **Final Sort**: By relevance score (desc), then by published_at (desc)

### Example Scoring
Reference Work: Category "color-grading", Tags ["commercial", "automotive", "luxury"]

| Related Work | Category Match | Shared Tags | Score | Rank |
|-------------|----------------|-------------|-------|------|
| Work A | color-grading | ["commercial", "luxury"] | 12 | 1st |
| Work B | color-grading | ["commercial"] | 11 | 2nd |
| Work C | vfx | ["commercial", "automotive", "luxury"] | 3 | 3rd |
| Work D | motion-graphics | ["commercial"] | 1 | 4th |

## Example Requests

### Basic Related Works
```
GET /api/v1/works-related/550e8400-e29b-41d4-a716-446655440000
```

### Limited Results
```
GET /api/v1/works-related/550e8400-e29b-41d4-a716-446655440000?limit=4
```

### Sort by Latest
```
GET /api/v1/works-related/550e8400-e29b-41d4-a716-446655440000?sort_by=latest&limit=6
```

### Sort by Year
```
GET /api/v1/works-related/550e8400-e29b-41d4-a716-446655440000?sort_by=year&limit=10
```

## Response Structure

### Success Response (200)
```json
{
  "success": true,
  "data": [
    {
      "id": "related-uuid-1",
      "title": "Mercedes AMG Campaign",
      "client": "Mercedes-Benz",
      "category": "color-grading",
      "year": 2024,
      "description": "Luxury automotive commercial with premium color treatment.",
      "slug": "mercedes-amg-campaign",
      "hero_banner_image": "https://example.com/images/mercedes-hero.jpg",
      "video_project_src": "https://example.com/videos/mercedes.mp4",
      "video_project_poster": "https://example.com/images/mercedes-poster.jpg",
      "tags": ["commercial", "automotive", "luxury"],
      "published_at": "2024-02-15T10:00:00.000Z",
      "display_order": 2,
      "match_info": {
        "category_match": true,
        "shared_tags": ["commercial", "automotive", "luxury"],
        "shared_tags_count": 3,
        "relevance_score": 13
      }
    },
    {
      "id": "related-uuid-2",
      "title": "BMW i8 Commercial",
      "client": "BMW",
      "category": "color-grading",
      "year": 2023,
      "description": "Electric vehicle showcase with cinematic color grading.",
      "slug": "bmw-i8-commercial",
      "hero_banner_image": "https://example.com/images/bmw-hero.jpg",
      "video_project_src": "https://example.com/videos/bmw.mp4",
      "video_project_poster": "https://example.com/images/bmw-poster.jpg",
      "tags": ["commercial", "automotive"],
      "published_at": "2023-11-20T14:30:00.000Z",
      "display_order": 3,
      "match_info": {
        "category_match": true,
        "shared_tags": ["commercial", "automotive"],
        "shared_tags_count": 2,
        "relevance_score": 12
      }
    },
    {
      "id": "related-uuid-3",
      "title": "Rolex VFX Campaign",
      "client": "Rolex",
      "category": "vfx",
      "year": 2024,
      "description": "Luxury watch commercial with advanced VFX work.",
      "slug": "rolex-vfx-campaign",
      "hero_banner_image": "https://example.com/images/rolex-hero.jpg",
      "video_project_src": "https://example.com/videos/rolex.mp4",
      "video_project_poster": "https://example.com/images/rolex-poster.jpg",
      "tags": ["commercial", "luxury"],
      "published_at": "2024-01-10T09:15:00.000Z",
      "display_order": 4,
      "match_info": {
        "category_match": false,
        "shared_tags": ["commercial", "luxury"],
        "shared_tags_count": 2,
        "relevance_score": 2
      }
    }
  ],
  "categorized": {
    "same_category": [
      {
        "id": "related-uuid-1",
        "title": "Mercedes AMG Campaign",
        // ... full work data
      },
      {
        "id": "related-uuid-2", 
        "title": "BMW i8 Commercial",
        // ... full work data
      }
    ],
    "shared_tags": [
      {
        "id": "related-uuid-3",
        "title": "Rolex VFX Campaign",
        // ... full work data
      }
    ],
    "other_related": []
  },
  "reference_work": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "title": "Audi Luxury Commercial",
    "category": "color-grading",
    "tags": ["commercial", "automotive", "luxury"]
  },
  "meta": {
    "total_found": 3,
    "limit": 8,
    "sort_by": "relevance",
    "same_category_count": 2,
    "shared_tags_count": 1,
    "other_related_count": 0
  },
  "message": "Related works retrieved successfully"
}
```

### Not Found Response (404)
```json
{
  "success": false,
  "message": "Reference work not found or not published",
  "data": [],
  "categorized": {
    "same_category": [],
    "shared_tags": [],
    "other_related": []
  }
}
```

### Error Response (500)
```json
{
  "success": false,
  "message": "Failed to retrieve related works",
  "data": [],
  "categorized": {
    "same_category": [],
    "shared_tags": [],
    "other_related": []
  },
  "error": "Internal server error"
}
```

## Features

### Smart Matching Algorithm
- **Category Priority**: Works from the same category are prioritized
- **Tag Similarity**: Multiple shared tags increase relevance score
- **Fallback Logic**: If no category/tag matches, returns other published works
- **Duplicate Prevention**: Never returns the reference work itself

### Categorized Results
Results are organized into three categories for flexible frontend use:
- **`same_category`**: Works from the same category (highest relevance)
- **`shared_tags`**: Works with shared tags but different category
- **`other_related`**: Works with no direct tag/category match (fallback)

### Match Information
Each related work includes detailed match information:
- **`category_match`**: Boolean indicating if categories match
- **`shared_tags`**: Array of tags shared with reference work
- **`shared_tags_count`**: Number of shared tags
- **`relevance_score`**: Calculated relevance score

### Security Features
- **Published Only**: Only returns works with status 'published' and published_at set
- **No Authentication**: Public access without token requirements
- **Input Validation**: UUID validation and parameter limits
- **Error Handling**: Comprehensive error responses

## Use Cases

### Work Detail Page
```javascript
// Show related works on work detail page
const { data: relatedWorks } = useQuery(
  ['related-works', workId],
  () => fetchRelatedWorks(workId, { limit: 4 })
);
```

### Portfolio Recommendations
```javascript
// More sophisticated recommendations
const { data: recommendations } = useQuery(
  ['recommendations', currentWorkId],
  () => fetchRelatedWorks(currentWorkId, { 
    sort_by: 'relevance',
    limit: 6 
  })
);
```

### Category-Specific Suggestions
```javascript
// Use categorized results for different sections
const sameCategory = relatedWorks.categorized.same_category;
const tagMatches = relatedWorks.categorized.shared_tags;
```

## Performance Considerations

### Database Optimization
- Efficient JSON tag matching with proper indexing
- Relevance scoring computed at database level
- Limited result sets to prevent large transfers

### Caching Strategy
- Cache related works per reference work ID
- Consider cache invalidation when works are updated
- TTL based on content update frequency

### Frontend Optimization
- Use match_info for intelligent display logic
- Implement lazy loading for related work images
- Cache API responses with appropriate TTL

This API provides intelligent content recommendations based on actual work relationships, perfect for keeping users engaged with relevant portfolio content.