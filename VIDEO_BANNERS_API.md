# Video Banners Public API

## Get Video Banners (Public)

**Endpoint:** `GET /api/v1/video-banners`

**Description:** Retrieve all active video banners for public display without authentication.

### Request
- **Method:** GET
- **URL:** `/api/v1/video-banners`
- **Authentication:** None required
- **Parameters:** None

### Response

#### Success Response (200)
```json
{
    "success": true,
    "data": [
        {
            "id": "uuid-string",
            "work_id": "work-uuid",
            "title": "PILLOW WALK",
            "client": "ALDO",
            "categories": ["COLOR GRADING"],
            "video_url": "https://videos.virtual-app.my.id/aldo_pillow_walk.mp4",
            "video_thumbnail": "https://example.com/thumbnail.jpg",
            "is_custom_video": false,
            "position": 1
        }
    ],
    "count": 4,
    "message": "Video banners retrieved successfully"
}
```

#### Error Response (500)
```json
{
    "success": false,
    "message": "Failed to retrieve video banners",
    "data": [],
    "count": 0
}
```

### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | string | Unique identifier for the video banner |
| `work_id` | string | Associated work/project ID |
| `title` | string | Project title |
| `client` | string | Client name |
| `categories` | array | Array of project categories/tags |
| `video_url` | string | URL to the video file |
| `video_thumbnail` | string | URL to the video thumbnail image |
| `is_custom_video` | boolean | Whether this is a custom video or project video |
| `position` | integer | Display order position |

### Usage Examples

#### JavaScript/Fetch
```javascript
fetch('/api/v1/video-banners')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Video banners:', data.data);
        }
    });
```

#### React Component
```jsx
import { useEffect, useState } from 'react';

function VideoBanners() {
    const [banners, setBanners] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetch('/api/v1/video-banners')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    setBanners(data.data);
                }
                setLoading(false);
            });
    }, []);

    if (loading) return <div>Loading...</div>;

    return (
        <div>
            {banners.map(banner => (
                <div key={banner.id}>
                    <h3>{banner.title} - {banner.client}</h3>
                    <video src={banner.video_url} poster={banner.video_thumbnail} />
                </div>
            ))}
        </div>
    );
}
```

### Notes
- Returns only active video banners (`is_active = true`)
- Results are ordered by position
- Maximum of 4 banners will be returned
- No authentication required
- CORS enabled for frontend access