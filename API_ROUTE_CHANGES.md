# API Route Changes Summary

## Updated Route Names

The following public works API routes have been renamed for better clarity:

### Changes Made:

| Old Route | New Route | Purpose |
|-----------|-----------|---------|
| `/api/v1/works-public` | `/api/v1/works-list` | Works listing with filtering |
| `/api/v1/works-public/{id}` | `/api/v1/works-detail/{id}` | Individual work details |

### Unchanged Routes:

The following routes remain the same:
- `/api/v1/works-latest` - Latest works by year and update date
- `/api/v1/works-related/{id}` - Related works based on tags/category
- `/api/v1/video-banners` - Video banners for homepage

## Complete Updated API Suite

### 1. Works List with Filtering
```
GET /api/v1/works-list
```
- Comprehensive filtering, search, sorting, pagination
- Multi-value tag and category filtering
- Custom sorting options

### 2. Work Detail
```
GET /api/v1/works-detail/{id}
```
- Complete work information with relationships
- Credits and gallery items
- Related works suggestions

### 3. Latest Works
```
GET /api/v1/works-latest
```
- Latest works sorted by year and updated_at
- Perfect for homepage sections

### 4. Related Works
```
GET /api/v1/works-related/{id}
```
- Intelligent recommendations based on tags/category
- Smart relevance scoring

### 5. Video Banners
```
GET /api/v1/video-banners
```
- Homepage video banners

## Updated Frontend Integration

### API Service Examples
```javascript
// Works list (previously works-public)
const worksList = await api.get('/works-list', {
  params: {
    tags: 'commercial,advertising',
    categories: 'color-grading,vfx',
    search: 'nike',
    page: 1,
    per_page: 12
  }
});

// Work detail (previously works-public/{id})
const workDetail = await api.get(`/works-detail/${workId}`);

// Unchanged routes
const latestWorks = await api.get('/works-latest?per_page=6');
const relatedWorks = await api.get(`/works-related/${workId}?limit=4`);
const videoBanners = await api.get('/video-banners');
```

### React Hooks Updates
```javascript
// Updated hooks for new route names
const useWorksList = (filters) => {
  return useQuery(
    ['works-list', filters],
    () => fetchWorksList(filters),
    { keepPreviousData: true }
  );
};

const useWorkDetail = (id) => {
  return useQuery(
    ['work-detail', id],
    () => fetchWorkDetail(id),
    { enabled: !!id }
  );
};

// Unchanged hooks
const useLatestWorks = (limit) => {
  return useQuery(['latest-works', limit], () => fetchLatestWorks({ per_page: limit }));
};

const useRelatedWorks = (id, options) => {
  return useQuery(['related-works', id, options], () => fetchRelatedWorks(id, options));
};
```

## Migration Notes

### For Frontend Developers:
1. Update API calls from `/works-public` to `/works-list`
2. Update API calls from `/works-public/{id}` to `/works-detail/{id}`
3. Update any hardcoded URLs in services or constants
4. Update React hooks and query keys as needed

### For Documentation:
1. All API documentation has been updated to reflect new route names
2. Examples and usage patterns have been updated
3. Frontend integration examples now use new routes

### Testing:
All routes have been verified and are working correctly:
- ✅ `/api/v1/works-list` - Works listing endpoint
- ✅ `/api/v1/works-detail/{id}` - Work detail endpoint
- ✅ `/api/v1/works-latest` - Latest works endpoint
- ✅ `/api/v1/works-related/{id}` - Related works endpoint
- ✅ `/api/v1/video-banners` - Video banners endpoint

The route changes improve API clarity by using more descriptive names that better indicate the purpose of each endpoint.