# Complete Public Works API Suite

## Overview
This document provides a complete overview of all public Works API endpoints that require no authentication and only return published content.

## Public API Endpoints

### 1. Works List with Filtering
```
GET /api/v1/works-list
```
**Purpose**: Comprehensive works listing with advanced filtering, search, sorting, and pagination
**Features**: 
- Multi-value tag filtering
- Multi-value category filtering  
- Search across title, client, description
- Custom sorting options
- Pagination with metadata
- Available filters data

### 2. Work Detail
```
GET /api/v1/works-detail/{id}
```
**Purpose**: Detailed information about a specific work
**Features**:
- Complete work information
- Full credits list
- Complete gallery items
- Related works suggestions (same category)
- Rich metadata

### 2. Work Detail
```
GET /api/v1/works-public/{id}
```
**Purpose**: Detailed information about a specific work
**Features**:
- Complete work information
- Full credits list
- Complete gallery items
- Related works suggestions (same category)
- Rich metadata

### 3. Latest Works
```
GET /api/v1/works-latest
```
**Purpose**: Latest works sorted by year and updated_at
**Features**:
- Sorted by year (desc) then updated_at (desc)
- Perfect for homepage "latest work" sections
- Pagination support
- Shows freshest content first

### 4. Related Works
```
GET /api/v1/works-related/{id}
```
**Purpose**: Intelligent related works based on tags and category matching
**Features**:
- Smart relevance scoring system
- Category and tag-based matching
- Categorized results (same category, shared tags, other)
- Match information and scoring details
- Configurable sorting and limits

## Common Security Features

All public APIs share these security characteristics:
- ✅ **Published Only**: Only returns works with `status = 'published'`
- ✅ **Published Date Required**: Only returns works with `published_at` not null
- ✅ **No Authentication**: Public access without tokens
- ✅ **Input Validation**: All parameters properly validated
- ✅ **Error Handling**: Comprehensive error responses
- ✅ **Rate Limiting**: Standard Laravel rate limiting applies

## Common Response Format

All APIs return consistent JSON structure:
```json
{
  "success": boolean,
  "data": array|object,
  "meta": {...},        // Pagination info (for list endpoints)
  "message": string,
  "...": "..."          // Additional endpoint-specific data
}
```

## Usage Comparison

| Endpoint | Use Case | Sorting | Filtering | Pagination | Matching |
|----------|----------|---------|-----------|------------|----------|
| `/works-list` | Portfolio page with filters | Customizable | Advanced | Yes | N/A |
| `/works-detail/{id}` | Work detail page | N/A | N/A | N/A | N/A |
| `/works-latest` | Homepage latest section | Fixed (year+updated) | None | Yes | N/A |
| `/works-related/{id}` | Related content recommendations | Relevance/Latest/Year | None | No | Smart AI-like |

## Frontend Integration Examples

### React Services
```javascript
// Latest works for homepage
const latestWorks = await api.get('/works-latest?per_page=6');

// Filtered works for portfolio page
const portfolioWorks = await api.get('/works-list', {
  params: {
    tags: 'commercial,advertising',
    categories: 'color-grading,vfx',
    search: 'nike',
    sort_by: 'year',
    sort_direction: 'desc',
    page: 1,
    per_page: 12
  }
});

// Work detail page
const workDetail = await api.get(`/works-detail/${workId}`);

// Related works for recommendations
const relatedWorks = await api.get(`/works-related/${workId}`, {
  params: {
    limit: 4,
    sort_by: 'relevance'
  }
});
```

### React Hooks Usage
```javascript
// Latest works hook
const useLatestWorks = (limit = 6) => {
  return useQuery(
    ['latest-works', limit],
    () => fetchLatestWorks({ per_page: limit })
  );
};

// Filtered works hook
const useFilteredWorks = (filters) => {
  return useQuery(
    ['works', filters],
    () => fetchPublicWorks(filters),
    { keepPreviousData: true }
  );
};

// Work detail hook
const useWorkDetail = (id) => {
  return useQuery(
    ['work', id],
    () => fetchWorkDetail(id),
    { enabled: !!id }
  );
};

// Related works hook
const useRelatedWorks = (id, options = {}) => {
  return useQuery(
    ['related-works', id, options],
    () => fetchRelatedWorks(id, options),
    { enabled: !!id }
  );
};
```

## Performance Considerations

### Database Optimization
- Proper indexing on `status`, `published_at`, `year`, `updated_at` fields
- Efficient eager loading of relationships (`credits`, `galleryItems`)
- Pagination to prevent large data transfers

### Caching Strategy
- Consider caching latest works (updates less frequently)
- Cache work details (rarely change once published)
- Cache filter data (categories, tags, years)

### Frontend Optimization
- Use pagination for large datasets
- Implement infinite scroll for better UX
- Cache API responses with proper TTL
- Use skeleton loading states

## API Evolution

### Current State (v1)
- Basic filtering and search
- Fixed sorting options
- Standard pagination

### Future Enhancements
- Advanced search with weighted results
- Content-based recommendations
- Geographic filtering
- Date range filtering
- Custom sorting combinations

## Error Handling

All endpoints provide consistent error responses:

### 404 Not Found (Detail endpoint only)
```json
{
  "success": false,
  "message": "Work not found or not published",
  "data": null
}
```

### 500 Server Error
```json
{
  "success": false,
  "message": "Failed to retrieve works",
  "data": [],
  "error": "Internal server error"  // Only in debug mode
}
```

### 422 Validation Error
```json
{
  "success": false,
  "message": "Invalid parameters",
  "errors": {
    "per_page": ["The per page field must be between 1 and 50."]
  }
}
```

This complete API suite provides all the functionality needed for a robust public-facing portfolio website with filtering, search, and detailed work display capabilities.