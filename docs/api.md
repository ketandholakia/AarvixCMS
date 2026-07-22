# AarvixCMS REST API

The AarvixCMS API provides read-only access to published content (Posts, Pages, Categories). It uses Laravel Sanctum for authentication.

## Base URL
All API endpoints are prefixed with `/api/v1/`. For example, `https://yourdomain.com/api/v1/posts`.

## Authentication
To access the API, you must pass a Bearer token in the `Authorization` header.

```http
Authorization: Bearer {your-token}
```

Tokens can be issued with specific permissions (abilities). Currently, the supported abilities are:
- `api.read`: Allows read access to all endpoints.
- `api.write`: Reserved for future use.

## Endpoints

### Posts
- **`GET /api/v1/posts`**
  Returns a paginated list of published posts.
  **Query Parameters:**
  - `page`: Page number (default: 1)
  - `per_page`: Number of posts per page (default: 15)
  - `category`: Filter by category slug
  - `tag`: Filter by tag slug

- **`GET /api/v1/posts/{slug}`**
  Returns a single published post by its slug.

### Pages
- **`GET /api/v1/pages`**
  Returns a list of all published pages.

- **`GET /api/v1/pages/{slug}`**
  Returns a single published page by its slug.

### Categories
- **`GET /api/v1/categories`**
  Returns a hierarchical list of all categories (includes children and post counts).

- **`GET /api/v1/categories/{slug}`**
  Returns a single category (including its children and post count).

## Response Format
The API follows standard JSON:API conventions where the main payload is wrapped in a `data` key.

**Example Response (`GET /api/v1/posts/hello-world`):**
```json
{
  "data": {
    "id": 1,
    "title": "Hello World",
    "slug": "hello-world",
    "excerpt": "This is my first post.",
    "body": "<p>Content goes here...</p>",
    "status": "published",
    "meta_title": "Hello World - My Site",
    "meta_description": "A description for SEO.",
    "author": {
      "id": 1,
      "name": "Admin",
      "created_at": "2026-07-20T10:00:00.000000Z"
    },
    "category": {
      "id": 2,
      "name": "General",
      "slug": "general"
    },
    "tags": [
      {
        "id": 1,
        "name": "News",
        "slug": "news"
      }
    ],
    "published_at": "2026-07-21T00:00:00.000000Z",
    "created_at": "2026-07-21T00:00:00.000000Z",
    "updated_at": "2026-07-21T00:00:00.000000Z"
  }
}
```

## Error Handling
If a resource is not found, the API returns a `404 Not Found`. If an invalid token is provided, the API returns a `401 Unauthorized`.
