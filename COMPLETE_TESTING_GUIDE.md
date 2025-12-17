# Complete Testing Guide - Loyalty System
**Generated:** December 2, 2025

---

## Table of Contents
1. [Admin Panel URLs](#admin-panel-urls)
2. [Merchant Panel URLs](#merchant-panel-urls)
3. [API Endpoints](#api-endpoints)
4. [Testing Procedures](#testing-procedures)
5. [Test Data Requirements](#test-data-requirements)

---

## Admin Panel URLs

**Base URL:** `http://your-domain.com/admin`

### Admin Authentication & Dashboard
- **Login:** `/admin/login`
- **Dashboard:** `/admin`

### Tenant Management
- **List Tenants:** `/admin/tenants`
- **Create Tenant:** `/admin/tenants/create`
- **Edit Tenant:** `/admin/tenants/{id}/edit`
- **View Tenant Details:** `/admin/tenants/{id}`

**Features to Test:**
- Create new merchant/tenant
- Edit tenant information (business name, slug, contact info)
- Set subscription plan and limits (max customers, max staff)
- Enable/disable tenant
- Upload logo
- View tenant statistics

### Global Customer Management
- **List All Customers:** `/admin/global-customers`
- **View Customer Details:** `/admin/global-customers/{id}`

**Features to Test:**
- View all customers across all tenants
- View customer's memberships in different merchants
- View customer transaction history across tenants
- Search and filter customers
- Customer verification status

---

## Merchant Panel URLs

**Base URL:** `http://your-domain.com/admin` (Multi-tenant, accessed per tenant)

### Merchant Authentication & Dashboard
- **Login:** `/admin/login` (tenant staff/owner login)
- **Dashboard:** `/admin` (shows merchant-specific data)

### Customer Membership Management
- **List Customers:** `/admin/customer-memberships`
- **View Customer:** `/admin/customer-memberships/{id}`
- **Edit Customer:** `/admin/customer-memberships/{id}/edit`

**Features to Test:**
- View all customers in the loyalty program
- View customer points and tier
- Manually adjust customer points (add/deduct)
- Update customer tier
- View customer transaction history
- View customer redemptions
- Search and filter customers by name, phone, tier

### Points Settings
- **Manage Points Settings:** `/admin/points-settings`

**Features to Test:**
- Set earning rules (points per currency unit)
- Set points multiplier
- Configure welcome bonus points
- Set points expiration rules (days)
- Enable/disable points expiration
- Test calculations with different settings

### Tier Management
- **List Tiers:** `/admin/tiers`
- **Create Tier:** `/admin/tiers/create`
- **Edit Tier:** `/admin/tiers/{id}/edit`

**Features to Test:**
- Create multiple tiers (Bronze, Silver, Gold, Platinum)
- Set minimum points required for each tier
- Set points multiplier for each tier
- Add tier benefits
- Set tier colors and icons
- Enable/disable tiers
- Automatic tier upgrade when customer reaches points threshold

### Reward Management
- **List Rewards:** `/admin/rewards`
- **Create Reward:** `/admin/rewards/create`
- **Edit Reward:** `/admin/rewards/{id}/edit`

**Features to Test:**
- Create different reward types:
  - Free Product
  - Percentage Discount
  - Fixed Amount Discount
  - Special Offer
- Set points required
- Set minimum tier requirement
- Set validity period (from/until dates)
- Set quantity limits
- Upload reward images
- Add terms and conditions (Arabic & English)
- Enable/disable rewards
- View redemption statistics

### Redemption Management
- **List Redemptions:** `/admin/redemptions`
- **View Redemption:** `/admin/redemptions/{id}`
- **Edit Redemption:** `/admin/redemptions/{id}/edit`

**Features to Test:**
- View pending redemption requests
- Approve redemptions
- Mark redemptions as used
- Reject/cancel redemptions
- View redemption codes
- Filter by status (pending, approved, used, rejected)
- View redemption history

### Transaction History
- **List Transactions:** `/admin/transactions`
- **View Transaction:** `/admin/transactions/{id}`

**Features to Test:**
- View all point transactions
- Filter by type (earn, redeem, bonus, expire, manual)
- Filter by date range
- Filter by customer
- View transaction details (staff member, amount, points)
- Export transaction reports

### Staff Management
- **List Staff:** `/admin/staff`
- **Create Staff:** `/admin/staff/create`
- **Edit Staff:** `/admin/staff/{id}/edit`

**Features to Test:**
- Create staff accounts
- Assign roles (staff, manager)
- Set permissions
- Enable/disable staff accounts
- View staff activity
- Staff can award points to customers

---

## API Endpoints

**Base URL:** `http://your-domain.com/api`

### 1. Authentication Endpoints

#### 1.1 Send OTP
- **URL:** `POST /api/auth/send-otp`
- **Body:**
```json
{
  "phone_number": "+962791234567"
}
```
**Test Cases:**
- Valid phone number
- Invalid phone format
- Missing phone number
- Rate limiting (30 requests/minute)

#### 1.2 Verify OTP
- **URL:** `POST /api/auth/verify-otp`
- **Body:**
```json
{
  "phone_number": "+962791234567",
  "otp": "123456",
  "full_name": "Ahmad Khalil",
  "email": "ahmad@example.com",
  "language": "ar"
}
```
**Test Cases:**
- Valid OTP
- Invalid OTP
- Expired OTP
- New customer registration
- Existing customer login
- Missing optional fields

#### 1.3 Login (Email & Password)
- **URL:** `POST /api/auth/login`
- **Body:**
```json
{
  "email": "ahmad@example.com",
  "password": "password123"
}
```
**Test Cases:**
- Valid credentials
- Invalid email
- Invalid password
- Non-existent user

#### 1.4 Register
- **URL:** `POST /api/auth/register`
- **Body:**
```json
{
  "phone_number": "+962791234567",
  "email": "ahmad@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "full_name": "Ahmad Khalil",
  "date_of_birth": "1990-01-15",
  "language": "ar"
}
```
**Test Cases:**
- Valid registration
- Duplicate phone number
- Duplicate email
- Password mismatch
- Missing required fields

#### 1.5 Logout
- **URL:** `POST /api/auth/logout`
- **Headers:** `Authorization: Bearer {token}`
**Test Cases:**
- Valid token logout
- Already logged out token

#### 1.6 Get Profile
- **URL:** `GET /api/auth/me`
- **Headers:** `Authorization: Bearer {token}`
**Test Cases:**
- Valid token
- Invalid token
- Expired token

#### 1.7 Update Profile
- **URL:** `PUT /api/auth/profile`
- **Headers:** `Authorization: Bearer {token}`
- **Body:**
```json
{
  "full_name": "Ahmad Updated",
  "email": "newemail@example.com",
  "date_of_birth": "1990-01-15",
  "language": "en"
}
```
**Test Cases:**
- Update name only
- Update email only
- Update multiple fields
- Duplicate email validation

---

### 2. Customer Membership Endpoints

#### 2.1 List All Memberships
- **URL:** `GET /api/memberships`
- **Headers:** `Authorization: Bearer {token}`
**Test Cases:**
- Customer with multiple memberships
- Customer with no memberships
- Response includes tier info and points

#### 2.2 Get Specific Membership
- **URL:** `GET /api/memberships/{tenant_slug}`
- **Headers:** `Authorization: Bearer {token}`
**Test Cases:**
- Valid membership
- Non-existent tenant
- Customer not a member

#### 2.3 Get Points & Tier Status
- **URL:** `GET /api/memberships/{tenant_slug}/points`
- **Headers:** `Authorization: Bearer {token}`
**Test Cases:**
- View current points
- View current tier
- View progress to next tier
- View all available tiers
- Customer at max tier (no next tier)

#### 2.4 Join Merchant Program
- **URL:** `POST /api/memberships/{tenant_slug}/join`
- **Headers:** `Authorization: Bearer {token}`
**Test Cases:**
- Successfully join new merchant
- Already a member (409 error)
- Merchant at customer limit (403 error)
- Welcome bonus points awarded
- QR code generated

---

### 3. Rewards & Redemptions Endpoints

#### 3.1 List Available Rewards
- **URL:** `GET /api/tenants/{tenant_slug}/rewards`
- **Headers:** `Authorization: Bearer {token}`
**Test Cases:**
- View all active rewards
- Rewards customer can redeem
- Rewards customer cannot redeem (tier/points)
- Filter by availability
- Expired rewards not shown

#### 3.2 Get Reward Details
- **URL:** `GET /api/tenants/{tenant_slug}/rewards/{reward_id}`
- **Headers:** `Authorization: Bearer {token}`
**Test Cases:**
- Valid reward
- Non-existent reward
- View terms and conditions
- Check eligibility

#### 3.3 Redeem Reward
- **URL:** `POST /api/tenants/{tenant_slug}/rewards/{reward_id}/redeem`
- **Headers:** `Authorization: Bearer {token}`
**Test Cases:**
- Successfully redeem reward
- Insufficient points (400 error)
- Tier too low (400 error)
- Reward unavailable
- Reward out of stock
- Points deducted correctly
- Redemption code generated
- Notification sent

#### 3.4 Get Redemption History
- **URL:** `GET /api/tenants/{tenant_slug}/redemptions`
- **Headers:** `Authorization: Bearer {token}`
**Test Cases:**
- View all redemptions
- View by status
- View expired redemptions
- Empty history

---

### 4. Transaction Endpoints

#### 4.1 Get Transaction History
- **URL:** `GET /api/tenants/{tenant_slug}/transactions`
- **Headers:** `Authorization: Bearer {token}`
- **Query Parameters:** `?per_page=20&type=earn&from_date=2025-01-01&to_date=2025-12-31`
**Test Cases:**
- Pagination works
- Filter by type (earn, redeem, bonus, expire)
- Filter by date range
- View transaction details
- Empty history

#### 4.2 Get Transaction Statistics
- **URL:** `GET /api/tenants/{tenant_slug}/transactions/stats`
- **Headers:** `Authorization: Bearer {token}`
**Test Cases:**
- View summary statistics
- View monthly breakdown (last 6 months)
- Total earned vs redeemed
- Last transaction details

#### 4.3 Get All Transactions (Global)
- **URL:** `GET /api/transactions`
- **Headers:** `Authorization: Bearer {token}`
- **Query Parameters:** `?per_page=20`
**Test Cases:**
- View transactions across all memberships
- Includes tenant information
- Pagination works

---

### 5. QR Code Endpoints

#### 5.1 Get QR Code for Merchant
- **URL:** `GET /api/tenants/{tenant_slug}/qr-code`
- **Headers:** `Authorization: Bearer {token}`
**Test Cases:**
- Generate QR code data
- QR code contains membership hash
- QR code includes customer info
- QR code includes current points

#### 5.2 Get All QR Codes
- **URL:** `GET /api/qr-codes`
- **Headers:** `Authorization: Bearer {token}`
**Test Cases:**
- Get QR codes for all memberships
- Each QR code has merchant info
- Empty if no memberships

#### 5.3 Validate QR Code (Staff Use)
- **URL:** `POST /api/qr-code/validate`
- **Headers:** `Authorization: Bearer {token}`
- **Body:**
```json
{
  "qr_code_hash": "abc123xyz..."
}
```
**Test Cases:**
- Valid QR code
- Invalid QR code hash
- Updates last_visit_at timestamp
- Returns customer and membership info

---

### 6. Notification Endpoints

#### 6.1 Get Notifications
- **URL:** `GET /api/notifications`
- **Headers:** `Authorization: Bearer {token}`
- **Query Parameters:** `?per_page=20&unread_only=false`
**Test Cases:**
- View all notifications
- View unread only
- Pagination works
- Language-specific content (AR/EN)

#### 6.2 Mark Notification as Read
- **URL:** `PUT /api/notifications/{id}/read`
- **Headers:** `Authorization: Bearer {token}`
**Test Cases:**
- Mark single notification as read
- Already read notification
- Non-existent notification

#### 6.3 Mark All as Read
- **URL:** `PUT /api/notifications/read-all`
- **Headers:** `Authorization: Bearer {token}`
**Test Cases:**
- Mark all unread as read
- Returns count of updated notifications
- No unread notifications

#### 6.4 Get Unread Count
- **URL:** `GET /api/notifications/unread-count`
- **Headers:** `Authorization: Bearer {token}`
**Test Cases:**
- Returns accurate count
- Zero unread notifications

#### 6.5 Delete Notification
- **URL:** `DELETE /api/notifications/{id}`
- **Headers:** `Authorization: Bearer {token}`
**Test Cases:**
- Successfully delete notification
- Non-existent notification
- Cannot delete other user's notification

#### 6.6 Update Device Token
- **URL:** `POST /api/notifications/device-token`
- **Headers:** `Authorization: Bearer {token}`
- **Body:**
```json
{
  "device_token": "ExponentPushToken[xxx...]",
  "device_type": "android"
}
```
**Test Cases:**
- Valid iOS token
- Valid Android token
- Invalid device type
- Missing token

---

### 7. Health Check
- **URL:** `GET /api/health`
**Test Cases:**
- API is running
- Returns version and timestamp

---

## Testing Procedures

### Admin Panel Testing

1. **Login as Super Admin**
   - Navigate to `/admin/login`
   - Use super admin credentials
   - Verify dashboard loads

2. **Tenant Management**
   - Create 3 test tenants (Cafe, Restaurant, Retail Store)
   - Set different subscription limits
   - Upload logos for each
   - Verify tenant slugs are unique
   - Test enable/disable functionality

3. **Global Customer View**
   - View all customers across tenants
   - Search for specific customer
   - View customer's multiple memberships
   - View cross-tenant transaction history

---

### Merchant Panel Testing

1. **Login as Merchant Owner**
   - Navigate to `/admin/login`
   - Use tenant-specific credentials
   - Verify only tenant data is visible

2. **Points Settings Configuration**
   - Set earning rate (e.g., 2 points per $1)
   - Set welcome bonus (e.g., 100 points)
   - Enable points expiration (e.g., 365 days)
   - Save and verify settings

3. **Tier Configuration**
   - Create 4 tiers: Bronze (0 pts), Silver (500 pts), Gold (1000 pts), Platinum (2000 pts)
   - Set multipliers: 1.0x, 1.2x, 1.5x, 2.0x
   - Add benefits for each tier
   - Set colors and icons
   - Activate all tiers

4. **Reward Creation**
   - Create "Free Coffee" - 200 points, Bronze tier, free_product
   - Create "10% Discount" - 300 points, Silver tier, percentage_discount
   - Create "20% Discount" - 500 points, Gold tier, percentage_discount
   - Create "VIP Treatment" - 1000 points, Platinum tier, special_offer
   - Set validity periods for each
   - Add Arabic and English descriptions

5. **Customer Management**
   - Add manual points to customer
   - Deduct points from customer
   - Verify tier upgrades automatically
   - View customer's transaction history
   - View customer's redemptions

6. **Redemption Processing**
   - View pending redemptions
   - Approve redemption request
   - Mark redemption as used
   - Verify redemption code is unique
   - Test rejection flow

7. **Staff Management**
   - Create staff member
   - Assign cashier role
   - Test staff can award points
   - Test staff cannot access admin features
   - Disable staff account

---

### Mobile API Testing (Using Postman/Insomnia)

#### Phase 1: Authentication
1. **Send OTP**
   - Send OTP to test phone number
   - Verify OTP received (check dev response or SMS)
   - Test invalid phone format
   - Test rate limiting

2. **Verify OTP & Register**
   - Verify OTP with valid code
   - Register new customer
   - Receive authentication token
   - Save token for subsequent requests

3. **Profile Management**
   - Get current profile
   - Update profile information
   - Change language preference (AR/EN)

#### Phase 2: Membership
1. **Join Merchant Programs**
   - Join 3 different test merchants
   - Verify welcome bonus received
   - Verify QR codes generated
   - Check starting tier is Bronze

2. **View Memberships**
   - List all memberships
   - View specific membership details
   - Check points and tier info
   - View progress to next tier

#### Phase 3: Earning Points
1. **Simulate Point Earning**
   - Staff awards points via admin panel
   - Customer receives notification
   - Check points balance updated
   - View transaction history
   - Verify tier upgrade when threshold reached

#### Phase 4: Rewards
1. **Browse Rewards**
   - View all available rewards
   - Check eligibility status
   - View rewards customer cannot redeem yet
   - View reward details with terms

2. **Redeem Rewards**
   - Redeem eligible reward
   - Verify points deducted
   - Receive redemption code
   - Get redemption notification
   - View redemption history

#### Phase 5: QR Codes
1. **Generate QR Code**
   - Get QR code for each merchant
   - Verify QR data is unique
   - Get all QR codes at once

2. **Validate QR Code (Staff Flow)**
   - Staff scans customer QR code
   - Validate QR code via API
   - View customer info and points
   - Award points to customer

#### Phase 6: Transactions
1. **View Transactions**
   - View all transactions for a merchant
   - Filter by type
   - Filter by date range
   - View global transactions across all merchants
   - Check transaction statistics

#### Phase 7: Notifications
1. **Notification Management**
   - View all notifications
   - View unread count
   - Mark single notification as read
   - Mark all as read
   - Delete notification
   - Register device token for push notifications

---

## Test Data Requirements

### Test Tenants
1. **Café Aroma**
   - Slug: `cafe-aroma`
   - Plan: Premium
   - Max Customers: 1000
   - Max Staff: 10

2. **Pizza Palace**
   - Slug: `pizza-palace`
   - Plan: Standard
   - Max Customers: 500
   - Max Staff: 5

3. **Fashion Store**
   - Slug: `fashion-store`
   - Plan: Basic
   - Max Customers: 100
   - Max Staff: 3

### Test Customers
1. **Ahmad Khalil**
   - Phone: +962791234567
   - Email: ahmad@example.com
   - Language: Arabic

2. **Sarah Johnson**
   - Phone: +962797654321
   - Email: sarah@example.com
   - Language: English

3. **Mohammed Ali**
   - Phone: +962799876543
   - Email: mohammed@example.com
   - Language: Arabic

### Test Scenarios

#### Scenario 1: New Customer Journey
1. Customer downloads mobile app
2. Registers with phone number (OTP)
3. Joins 2 merchants
4. Receives welcome bonus
5. Makes purchase, earns points
6. Views available rewards
7. Redeems first reward
8. Uses redemption code in store

#### Scenario 2: Tier Progress
1. Customer starts at Bronze (0 points)
2. Earns 500 points → Auto upgrade to Silver
3. Earns 500 more → Auto upgrade to Gold (1000 total)
4. Benefits and multiplier increase
5. More rewards become available

#### Scenario 3: Multi-Merchant
1. Customer joins 3 different merchants
2. Earns points from all 3
3. Different tier levels in each merchant
4. Redeems rewards from different merchants
5. Views global transaction history

#### Scenario 4: Points Expiration
1. Set expiration to 30 days for testing
2. Award points to customer
3. Wait 30 days (or manually trigger)
4. Points expire automatically
5. Transaction created for expiration
6. Customer notified

#### Scenario 5: Staff Operations
1. Staff member logs into merchant panel
2. Customer presents QR code
3. Staff validates QR code
4. Staff awards points for purchase
5. Customer receives notification
6. Transaction recorded with staff info

---

## API Testing Checklist

### Authentication
- [ ] Send OTP - Valid phone
- [ ] Send OTP - Invalid phone
- [ ] Send OTP - Rate limiting
- [ ] Verify OTP - Valid code
- [ ] Verify OTP - Invalid code
- [ ] Verify OTP - Expired code
- [ ] Login - Valid credentials
- [ ] Login - Invalid credentials
- [ ] Register - New customer
- [ ] Register - Duplicate phone/email
- [ ] Logout - Valid token
- [ ] Get Profile - Authenticated
- [ ] Update Profile - Valid data

### Memberships
- [ ] List all memberships
- [ ] Get specific membership
- [ ] Get points and tier status
- [ ] Join merchant program
- [ ] Join - Already member (409)
- [ ] Join - Merchant at limit (403)

### Rewards
- [ ] List available rewards
- [ ] Get reward details
- [ ] Redeem reward - Success
- [ ] Redeem - Insufficient points
- [ ] Redeem - Tier too low
- [ ] View redemption history

### Transactions
- [ ] Get transaction history
- [ ] Filter by type
- [ ] Filter by date range
- [ ] Get statistics
- [ ] Get global transactions
- [ ] Pagination works

### QR Codes
- [ ] Get QR code for merchant
- [ ] Get all QR codes
- [ ] Validate QR code
- [ ] Invalid QR code hash

### Notifications
- [ ] Get all notifications
- [ ] Get unread count
- [ ] Mark as read
- [ ] Mark all as read
- [ ] Delete notification
- [ ] Update device token

### Error Handling
- [ ] 401 - Unauthorized
- [ ] 403 - Forbidden
- [ ] 404 - Not Found
- [ ] 422 - Validation Error
- [ ] 429 - Rate Limit Exceeded
- [ ] 500 - Server Error

---

## Admin Panel Testing Checklist

### Super Admin Features
- [ ] Login as super admin
- [ ] View admin dashboard
- [ ] Create new tenant
- [ ] Edit tenant information
- [ ] Set tenant limits
- [ ] Enable/disable tenant
- [ ] View all global customers
- [ ] Search customers
- [ ] View customer details
- [ ] View cross-tenant data

### Merchant Admin Features
- [ ] Login as merchant owner
- [ ] View merchant dashboard
- [ ] Configure points settings
- [ ] Create tiers
- [ ] Edit tiers
- [ ] Create rewards
- [ ] Edit rewards
- [ ] View customer list
- [ ] View customer details
- [ ] Award/deduct points manually
- [ ] View redemptions
- [ ] Approve redemptions
- [ ] Mark redemptions as used
- [ ] View transactions
- [ ] Filter transactions
- [ ] Create staff member
- [ ] Edit staff member
- [ ] Assign staff roles
- [ ] Disable staff account

---

## Performance Testing

### Load Testing Targets
- API should handle 100 requests/minute per user
- Page load time < 2 seconds
- API response time < 500ms
- QR code validation < 200ms
- Transaction processing < 1 second

### Stress Testing Scenarios
1. 1000 concurrent users
2. 10,000 transactions per hour
3. 1000 redemptions per hour
4. 50 staff members awarding points simultaneously

---

## Security Testing

### Authentication Security
- [ ] Test SQL injection in login
- [ ] Test XSS in input fields
- [ ] Test CSRF protection
- [ ] Test token expiration
- [ ] Test token revocation
- [ ] Test rate limiting
- [ ] Test password hashing (bcrypt)

### Authorization Security
- [ ] Customer cannot access other customer's data
- [ ] Staff cannot access admin features
- [ ] Tenant cannot access other tenant's data
- [ ] Test API authentication middleware

### Data Security
- [ ] Sensitive data encrypted
- [ ] HTTPS enforced
- [ ] Secure headers present
- [ ] No sensitive data in logs
- [ ] Database credentials secured

---

## Browser Compatibility Testing

### Admin Panel
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Chrome
- [ ] Mobile Safari

---

## Mobile App Testing (When Implemented)

### iOS Testing
- [ ] Registration flow
- [ ] Login flow
- [ ] View memberships
- [ ] Browse rewards
- [ ] Redeem rewards
- [ ] View QR codes
- [ ] View transactions
- [ ] View notifications
- [ ] Push notifications
- [ ] Arabic language support
- [ ] English language support

### Android Testing
- [ ] Same as iOS checklist

---

## Regression Testing

After any code changes, re-test:
1. Authentication flow
2. Points earning and redemption
3. Tier upgrades
4. QR code validation
5. Notifications
6. Critical business logic

---

## Bug Reporting Template

```
Title: [Brief description]
Severity: Critical / High / Medium / Low
Environment: Production / Staging / Development
Steps to Reproduce:
1.
2.
3.
Expected Result:
Actual Result:
Screenshots:
Additional Notes:
```

---

**End of Testing Guide**
