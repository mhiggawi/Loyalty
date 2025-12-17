# API Documentation - Loyalty System

**Base URL:** `https://your-domain.com/api`

**Version:** 1.0.0

**Date:** November 29, 2025

---

## Table of Contents

1. [Authentication](#authentication)
2. [Customer Memberships](#customer-memberships)
3. [Rewards & Redemptions](#rewards--redemptions)
4. [Transactions](#transactions)
5. [QR Codes](#qr-codes)
6. [Notifications](#notifications)
7. [Rate Limiting](#rate-limiting)
8. [Error Handling](#error-handling)

---

## Authentication

All endpoints use **Laravel Sanctum** for token-based authentication.

### 1. Send OTP

Send a one-time password to the customer's phone number.

**Endpoint:** `POST /api/auth/send-otp`

**Request Body:**
```json
{
  "phone_number": "+962791234567"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "OTP sent successfully",
  "expires_in": 300,
  "otp": "123456"  // Only in development environment
}
```

**Validation:**
- `phone_number`: Required, valid phone number format (+1-9 followed by 1-14 digits)

**Rate Limit:** 30 requests/minute (unauthenticated)

---

### 2. Verify OTP & Login/Register

Verify the OTP and create or login customer.

**Endpoint:** `POST /api/auth/verify-otp`

**Request Body:**
```json
{
  "phone_number": "+962791234567",
  "otp": "123456",
  "full_name": "Ahmad Khalil",  // Optional, for new customers
  "email": "ahmad@example.com",  // Optional
  "language": "ar"  // Optional: 'ar' or 'en'
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Authentication successful",
  "data": {
    "customer": {
      "id": 1,
      "full_name": "Ahmad Khalil",
      "phone_number": "+962791234567",
      "email": "ahmad@example.com",
      "language": "ar",
      "phone_verified": true,
      "email_verified": false
    },
    "token": "1|abc123xyz...",
    "token_type": "Bearer"
  }
}
```

**Validation:**
- `phone_number`: Required
- `otp`: Required, exactly 6 digits
- `full_name`: Optional, max 255 characters
- `email`: Optional, valid email, unique
- `language`: Optional, must be 'ar' or 'en'

---

### 3. Login (Email & Password)

Alternative authentication method using email and password.

**Endpoint:** `POST /api/auth/login`

**Request Body:**
```json
{
  "email": "ahmad@example.com",
  "password": "password123"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "customer": { ... },
    "token": "1|abc123xyz...",
    "token_type": "Bearer"
  }
}
```

---

### 4. Register (Email & Password)

Register new customer with email and password.

**Endpoint:** `POST /api/auth/register`

**Request Body:**
```json
{
  "phone_number": "+962791234567",
  "email": "ahmad@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "full_name": "Ahmad Khalil",
  "date_of_birth": "1990-01-15",  // Optional
  "language": "ar"  // Optional
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "customer": { ... },
    "token": "1|abc123xyz...",
    "token_type": "Bearer"
  }
}
```

---

### 5. Logout

Revoke the current access token.

**Endpoint:** `POST /api/auth/logout`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

### 6. Get Profile

Get authenticated customer profile.

**Endpoint:** `GET /api/auth/me`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "customer": {
      "id": 1,
      "full_name": "Ahmad Khalil",
      "phone_number": "+962791234567",
      "email": "ahmad@example.com",
      "date_of_birth": "1990-01-15",
      "language": "ar",
      "phone_verified": true,
      "email_verified": false,
      "created_at": "2025-01-15T10:30:00Z"
    }
  }
}
```

---

### 7. Update Profile

Update customer profile information.

**Endpoint:** `PUT /api/auth/profile`

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "full_name": "Ahmad Khalil Updated",
  "email": "newemail@example.com",
  "date_of_birth": "1990-01-15",
  "language": "en"
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Profile updated successfully",
  "data": {
    "customer": { ... }
  }
}
```

---

## Customer Memberships

Manage customer memberships across different merchants.

### 1. List All Memberships

Get all customer's memberships.

**Endpoint:** `GET /api/memberships`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "memberships": [
      {
        "id": 1,
        "tenant": {
          "id": 1,
          "business_name": "Café Aroma",
          "business_slug": "cafe-aroma",
          "logo_url": "https://..."
        },
        "current_points": 1250,
        "lifetime_points": 3450,
        "tier": {
          "level": "gold",
          "name": "Gold Member",
          "icon": "🥇",
          "color": "#FFD700",
          "multiplier": 1.5
        },
        "qr_code_hash": "abc123xyz...",
        "joined_at": "2025-01-01T00:00:00Z",
        "last_visit": "2025-11-28T14:30:00Z"
      }
    ],
    "total_count": 3
  }
}
```

---

### 2. Get Specific Membership

Get membership details for a specific merchant.

**Endpoint:** `GET /api/memberships/{tenant_slug}`

**Headers:**
```
Authorization: Bearer {token}
```

**Path Parameters:**
- `tenant_slug`: Merchant's business slug (e.g., "cafe-aroma")

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "membership": {
      "id": 1,
      "tenant": {
        "id": 1,
        "business_name": "Café Aroma",
        "business_slug": "cafe-aroma",
        "logo_url": "https://...",
        "contact_email": "info@cafearoma.com",
        "contact_phone": "+962791234567"
      },
      "current_points": 1250,
      "lifetime_points": 3450,
      "tier": { ... },
      "qr_code_hash": "abc123xyz...",
      "joined_at": "2025-01-01T00:00:00Z",
      "last_visit": "2025-11-28T14:30:00Z"
    }
  }
}
```

---

### 3. Get Points & Tier Status

Get detailed points, tier status, and progress to next tier.

**Endpoint:** `GET /api/memberships/{tenant_slug}/points`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "current_points": 1250,
    "lifetime_points": 3450,
    "current_tier": {
      "level": "gold",
      "name": "Gold Member",
      "icon": "🥇",
      "color": "#FFD700",
      "multiplier": 1.5,
      "benefits": ["Priority seating", "Free birthday dessert"]
    },
    "tier_progress": {
      "next_tier": {
        "level": "platinum",
        "name": "Platinum VIP",
        "icon": "💎",
        "color": "#E5E4E2",
        "min_points_required": 2000
      },
      "points_to_next_tier": 750,
      "progress_percentage": 62.5
    },
    "all_tiers": [
      {
        "level": "bronze",
        "name": "Bronze Member",
        "icon": "🥉",
        "color": "#CD7F32",
        "min_points": 0,
        "multiplier": 1.0,
        "is_current": false,
        "is_unlocked": true
      },
      {
        "level": "gold",
        "name": "Gold Member",
        "icon": "🥇",
        "color": "#FFD700",
        "min_points": 500,
        "multiplier": 1.5,
        "is_current": true,
        "is_unlocked": true
      },
      {
        "level": "platinum",
        "name": "Platinum VIP",
        "icon": "💎",
        "color": "#E5E4E2",
        "min_points": 2000,
        "multiplier": 2.0,
        "is_current": false,
        "is_unlocked": false
      }
    ]
  }
}
```

---

### 4. Join Merchant Program

Join a merchant's loyalty program.

**Endpoint:** `POST /api/memberships/{tenant_slug}/join`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Successfully joined merchant loyalty program",
  "data": {
    "membership": {
      "id": 5,
      "current_points": 100,  // Welcome bonus if configured
      "tier": {
        "level": "bronze",
        "name": "Bronze"
      },
      "qr_code_hash": "xyz789abc...",
      "joined_at": "2025-11-29T10:00:00Z"
    }
  }
}
```

**Errors:**
- `409 Conflict`: Already a member
- `403 Forbidden`: Merchant reached customer limit
- `404 Not Found`: Merchant not found

---

## Rewards & Redemptions

Browse rewards and redeem them.

### 1. List Available Rewards

Get all available rewards for a merchant.

**Endpoint:** `GET /api/tenants/{tenant_slug}/rewards`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "rewards": [
      {
        "id": 1,
        "title": "Free Coffee",
        "title_ar": "قهوة مجانية",
        "description": "Get a free medium coffee of your choice",
        "description_ar": "احصل على قهوة متوسطة مجانية من اختيارك",
        "reward_type": "free_product",
        "points_required": 200,
        "discount_value": null,
        "image_url": "https://...",
        "min_tier_required": "bronze",
        "quantity_available": 50,
        "valid_until": "2025-12-31T23:59:59Z",
        "can_redeem": true,
        "redemption_status": {
          "has_enough_points": true,
          "tier_eligible": true,
          "is_available": true,
          "points_needed": 0
        }
      },
      {
        "id": 2,
        "title": "20% Discount",
        "title_ar": "خصم 20%",
        "description": "20% off your entire purchase",
        "description_ar": "خصم 20% على مشترياتك",
        "reward_type": "percentage_discount",
        "points_required": 500,
        "discount_value": 20,
        "image_url": null,
        "min_tier_required": "silver",
        "quantity_available": null,
        "valid_until": null,
        "can_redeem": false,
        "redemption_status": {
          "has_enough_points": true,
          "tier_eligible": false,
          "is_available": true,
          "points_needed": 0
        }
      }
    ],
    "customer_points": 1250,
    "customer_tier": "gold"
  }
}
```

---

### 2. Get Reward Details

Get detailed information about a specific reward.

**Endpoint:** `GET /api/tenants/{tenant_slug}/rewards/{reward_id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "reward": {
      "id": 1,
      "title": "Free Coffee",
      "title_ar": "قهوة مجانية",
      "description": "...",
      "description_ar": "...",
      "terms": "Valid for one medium-sized drink. Cannot be combined with other offers.",
      "terms_ar": "صالح لمشروب واحد متوسط الحجم. لا يمكن دمجه مع عروض أخرى.",
      "reward_type": "free_product",
      "points_required": 200,
      "discount_value": null,
      "image_url": "https://...",
      "min_tier_required": "bronze",
      "quantity_available": 50,
      "valid_from": "2025-01-01T00:00:00Z",
      "valid_until": "2025-12-31T23:59:59Z",
      "can_redeem": true,
      "redemption_status": {
        "has_enough_points": true,
        "tier_eligible": true,
        "is_available": true,
        "points_needed": 0
      }
    },
    "customer_points": 1250,
    "customer_tier": "gold"
  }
}
```

---

### 3. Redeem Reward

Initiate a redemption request.

**Endpoint:** `POST /api/tenants/{tenant_slug}/rewards/{reward_id}/redeem`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Reward redeemed successfully",
  "data": {
    "redemption": {
      "id": 15,
      "redemption_code": "ABC12XYZ",
      "reward": {
        "title": "Free Coffee",
        "description": "...",
        "type": "free_product"
      },
      "points_used": 200,
      "status": "pending",
      "expires_at": "2025-12-29T10:00:00Z",
      "created_at": "2025-11-29T10:00:00Z"
    },
    "new_balance": 1050
  }
}
```

**Errors:**
- `400 Bad Request`: Cannot redeem (insufficient points, tier too low, or unavailable)
- `404 Not Found`: Reward or membership not found

---

### 4. Get Redemption History

Get customer's redemption history for a merchant.

**Endpoint:** `GET /api/tenants/{tenant_slug}/redemptions`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "redemptions": [
      {
        "id": 15,
        "redemption_code": "ABC12XYZ",
        "reward": {
          "title": "Free Coffee",
          "title_ar": "قهوة مجانية",
          "type": "free_product",
          "image_url": "https://..."
        },
        "points_used": 200,
        "status": "pending",
        "requested_at": "2025-11-29T10:00:00Z",
        "approved_at": null,
        "used_at": null,
        "expires_at": "2025-12-29T10:00:00Z",
        "is_expired": false
      }
    ],
    "total_count": 12
  }
}
```

---

## Transactions

View transaction history and statistics.

### 1. Get Transaction History

Get paginated transaction history for a merchant.

**Endpoint:** `GET /api/tenants/{tenant_slug}/transactions`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `per_page`: Optional, items per page (default: 20, max: 100)
- `type`: Optional, filter by transaction type (earn, redeem, bonus, referral, etc.)
- `from_date`: Optional, filter from date (YYYY-MM-DD)
- `to_date`: Optional, filter to date (YYYY-MM-DD)

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "transactions": [
      {
        "id": 123,
        "type": "earn",
        "type_label": "Points Earned",
        "points": 50,
        "amount": 25.50,
        "balance_after": 1250,
        "description": "Purchase at Café Aroma",
        "created_at": "2025-11-28T14:30:00Z",
        "staff": {
          "name": "Sarah Ahmad"
        }
      },
      {
        "id": 122,
        "type": "redeem",
        "type_label": "Points Redeemed",
        "points": -200,
        "amount": null,
        "balance_after": 1200,
        "description": "Redeemed: Free Coffee",
        "created_at": "2025-11-27T10:15:00Z",
        "staff": null
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 5,
      "per_page": 20,
      "total": 95,
      "from": 1,
      "to": 20
    },
    "filters": {
      "type": null,
      "from_date": null,
      "to_date": null
    }
  }
}
```

---

### 2. Get Transaction Statistics

Get statistical summary of transactions.

**Endpoint:** `GET /api/tenants/{tenant_slug}/transactions/stats`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "summary": {
      "current_points": 1250,
      "lifetime_points": 3450,
      "total_earned": 3650,
      "total_redeemed": 2200,
      "total_expired": 200,
      "transaction_count": 95
    },
    "last_transaction": {
      "type": "earn",
      "type_label": "Points Earned",
      "points": 50,
      "created_at": "2025-11-28T14:30:00Z"
    },
    "monthly_breakdown": [
      {
        "month": "Jun 2025",
        "earned": 450,
        "redeemed": 200,
        "net": 250
      },
      {
        "month": "Jul 2025",
        "earned": 520,
        "redeemed": 400,
        "net": 120
      }
    ]
  }
}
```

---

### 3. Get All Transactions

Get transactions across all memberships.

**Endpoint:** `GET /api/transactions`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `per_page`: Optional, items per page (default: 20, max: 100)

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "transactions": [
      {
        "id": 123,
        "tenant": {
          "business_name": "Café Aroma",
          "business_slug": "cafe-aroma",
          "logo_url": "https://..."
        },
        "type": "earn",
        "type_label": "Points Earned",
        "points": 50,
        "amount": 25.50,
        "balance_after": 1250,
        "description": "Purchase at Café Aroma",
        "created_at": "2025-11-28T14:30:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 8,
      "per_page": 20,
      "total": 152
    }
  }
}
```

---

## QR Codes

Generate and manage QR codes for customer identification.

### 1. Get QR Code for Merchant

Get QR code data for a specific merchant membership.

**Endpoint:** `GET /api/tenants/{tenant_slug}/qr-code`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "qr_code_data": "{\"membership_hash\":\"abc123...\",\"customer_id\":1,\"tenant_slug\":\"cafe-aroma\",\"timestamp\":1732876800}",
    "qr_code_hash": "abc123xyz...",
    "customer": {
      "name": "Ahmad Khalil",
      "phone": "+962791234567"
    },
    "membership": {
      "current_points": 1250,
      "tier_level": "gold"
    },
    "merchant": {
      "business_name": "Café Aroma",
      "business_slug": "cafe-aroma"
    }
  }
}
```

**Usage:**
- Use `qr_code_data` to generate QR code on mobile app
- QR code contains membership hash for staff scanning

---

### 2. Get All QR Codes

Get QR codes for all customer memberships.

**Endpoint:** `GET /api/qr-codes`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "qr_codes": [
      {
        "merchant": {
          "business_name": "Café Aroma",
          "business_slug": "cafe-aroma",
          "logo_url": "https://..."
        },
        "qr_code_data": "{...}",
        "qr_code_hash": "abc123...",
        "membership": {
          "current_points": 1250,
          "tier_level": "gold",
          "tier_name": "Gold Member"
        }
      }
    ],
    "customer": {
      "name": "Ahmad Khalil",
      "phone": "+962791234567"
    }
  }
}
```

---

### 3. Validate QR Code (Staff Use)

Validate a QR code hash and retrieve customer information.

**Endpoint:** `POST /api/qr-code/validate`

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "qr_code_hash": "abc123xyz..."
}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "customer": {
      "id": 1,
      "full_name": "Ahmad Khalil",
      "phone_number": "+962791234567",
      "email": "ahmad@example.com",
      "language": "ar"
    },
    "membership": {
      "id": 5,
      "current_points": 1250,
      "lifetime_points": 3450,
      "tier_level": "gold",
      "tier_name": "Gold Member",
      "tier_icon": "🥇",
      "tier_color": "#FFD700",
      "points_multiplier": 1.5,
      "joined_at": "Jan 01, 2025",
      "last_visit": "Nov 28, 2025 02:30 PM"
    },
    "merchant": {
      "business_name": "Café Aroma",
      "business_slug": "cafe-aroma"
    }
  }
}
```

**Note:** Automatically updates `last_visit_at` timestamp.

---

## Notifications

Manage customer notifications.

### 1. Get Notifications

Get paginated list of notifications.

**Endpoint:** `GET /api/notifications`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `per_page`: Optional, items per page (default: 20, max: 100)
- `unread_only`: Optional, boolean (default: false)

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "notifications": [
      {
        "id": 45,
        "type": "points_earned",
        "title": "نقاط جديدة",
        "message": "لقد حصلت على 50 نقطة من عملية الشراء",
        "is_read": false,
        "created_at": "2025-11-28T14:30:00Z",
        "read_at": null,
        "tenant": {
          "business_name": "Café Aroma",
          "business_slug": "cafe-aroma",
          "logo_url": "https://..."
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 3,
      "per_page": 20,
      "total": 56
    }
  }
}
```

**Note:** Title and message are returned based on customer's language preference.

---

### 2. Mark Notification as Read

Mark a single notification as read.

**Endpoint:** `PUT /api/notifications/{id}/read`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Notification marked as read"
}
```

---

### 3. Mark All as Read

Mark all notifications as read.

**Endpoint:** `PUT /api/notifications/read-all`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "All notifications marked as read",
  "count": 12
}
```

---

### 4. Get Unread Count

Get count of unread notifications.

**Endpoint:** `GET /api/notifications/unread-count`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "unread_count": 5
  }
}
```

---

### 5. Delete Notification

Delete a notification.

**Endpoint:** `DELETE /api/notifications/{id}`

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Notification deleted"
}
```

---

### 6. Update Device Token

Update device token for push notifications.

**Endpoint:** `POST /api/notifications/device-token`

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "device_token": "ExponentPushToken[xxx...]",
  "device_type": "android"
}
```

**Validation:**
- `device_token`: Required, string
- `device_type`: Required, must be 'ios' or 'android'

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Device token updated successfully"
}
```

---

## Rate Limiting

### Limits

- **Unauthenticated requests:** 30 requests per minute per IP
- **Authenticated requests:** 100 requests per minute per user

### Headers

All responses include rate limit headers:

```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 87
X-RateLimit-Reset: 1732876860
```

### Rate Limit Exceeded Response

**Status Code:** 429 Too Many Requests

```json
{
  "success": false,
  "message": "Too many requests. Please try again later.",
  "retry_after": 45,
  "retry_after_human": "00:45"
}
```

**Headers:**
```
Retry-After: 45
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1732876860
```

---

## Error Handling

### Standard Error Response Format

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

### HTTP Status Codes

| Code | Meaning | Description |
|------|---------|-------------|
| 200 | OK | Request successful |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request data |
| 401 | Unauthorized | Invalid or missing authentication |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource not found |
| 409 | Conflict | Resource conflict (e.g., already exists) |
| 422 | Unprocessable Entity | Validation error |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Internal Server Error | Server error |

### Common Error Examples

**Validation Error (422):**
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "phone_number": ["The phone number field is required."],
    "otp": ["The otp must be 6 characters."]
  }
}
```

**Unauthorized (401):**
```json
{
  "success": false,
  "message": "Invalid or expired OTP"
}
```

**Not Found (404):**
```json
{
  "success": false,
  "message": "Merchant not found"
}
```

**Forbidden (403):**
```json
{
  "success": false,
  "message": "This merchant has reached its customer limit"
}
```

---

## Security Headers

All API responses include security headers:

```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: default-src 'self'
```

---

## Health Check

Check API availability.

**Endpoint:** `GET /api/health`

**Response (200 OK):**
```json
{
  "success": true,
  "message": "API is running",
  "timestamp": "2025-11-29T10:00:00Z",
  "version": "1.0.0"
}
```

---

## Authentication Flow

### Mobile App Authentication Flow

1. **Send OTP**: Customer enters phone number → `POST /api/auth/send-otp`
2. **Verify OTP**: Customer enters 6-digit OTP → `POST /api/auth/verify-otp`
3. **Receive Token**: Store the Bearer token securely
4. **Make Authenticated Requests**: Include token in Authorization header
5. **Token Refresh**: Tokens don't expire (revoke via logout)

### Example Request with Authentication

```bash
curl -X GET https://your-domain.com/api/memberships \
  -H "Authorization: Bearer 1|abc123xyz..." \
  -H "Accept: application/json"
```

---

## Pagination

### Pagination Parameters

- `per_page`: Items per page (default: 20, max: 100)
- `page`: Page number (default: 1)

### Pagination Response Format

```json
{
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "per_page": 20,
    "total": 95,
    "from": 1,
    "to": 20
  }
}
```

---

## Language Support

The API supports Arabic (ar) and English (en).

- Set customer language preference via `language` field in registration/profile
- Notifications and some content are returned in customer's preferred language
- Use `title_en`/`title_ar` and `description_en`/`description_ar` fields where available

---

## Testing

### Development Environment

In development mode (`app.env !== 'production'`), the OTP is returned in the response for testing:

```json
{
  "success": true,
  "message": "OTP sent successfully",
  "otp": "123456"
}
```

**Remove this in production!**

---

## Support

For API support or questions:
- **Email:** api-support@loyalty-system.com
- **Documentation:** https://docs.loyalty-system.com

---

**Last Updated:** November 29, 2025
**API Version:** 1.0.0
