# Tenant Provisioning & Management API Reference

وثيقة ودليل تكامل الـ API لإنشاء وإدارة الـ Tenants (المستأجرين / الفروع والمشتركين) من خلال أنظمة خارجية (External Systems / CRM / Landing Pages / Billing Gateways).

---

## 1. Authentication & Security (المصادقة والأمان)

- **نوع المصادقة:** Bearer Token (Static Secret Token)
- **الهيدر المطلوب:** `Authorization: Bearer <TENANT_PROVISIONING_API_TOKEN>`
- **هيدر صيغة البيانات:** `Accept: application/json` و `Content-Type: application/json`
- **معدل الطلبات (Rate Limiter):** 10 طلبات لكل دقيقة لكل عنوان IP (`throttle:tenant-provisioning`).

### متغيرات البيئة (`.env`):
يجب التأكد من وجود المتغير التالي في ملف `.env`:
```dotenv
TENANT_PROVISIONING_API_TOKEN=your_secure_random_token_here
```

---

## 2. Endpoints (نقاط النهاية)

### 2.1 التحقق من توفر الدومين (Check Domain Availability)

تُستخدم هذه النقطة للتحقق مسبقاً مما إذا كان اسم الدومين محجوزاً أو متاحاً للتسجيل قبل إرسال طلب إنشاء الـ Tenant.

- **Method:** `GET` أو `POST`
- **URL:** `https://your-domain.com/api/tenants/check-domain`
- **Headers:**
  ```http
  Authorization: Bearer <TENANT_PROVISIONING_API_TOKEN>
  Accept: application/json
  ```
- **Query Parameter (في حال GET):** `?domain=client1.ps-web.test`
- **Request Body (في حال POST):**
  ```json
  {
    "domain": "client1.ps-web.test"
  }
  ```

#### Responses:

##### عند توفر الدومين (متاح للاستخدام):
```json
{
  "domain": "client1.ps-web.test",
  "exists": false,
  "available": true,
  "message": "الدومين متاح للاستخدام"
}
```

##### عند حجز الدومين مسبقاً (مستخدم بالفعل):
```json
{
  "domain": "client1.ps-web.test",
  "exists": true,
  "available": false,
  "message": "الدومين مستخدم بالفعل"
}
```

---

### 2.2 إنشاء تينت جديد (Provision Tenant)

تقوم هذه النقطة بالتحقق من البيانات وإرسال مهمة غير متزامنة (Queue Job) لإنشاء قاعدة البيانات الخاصة بالـ Tenant وتهيئتها مع إنشاء الحسابات وإرسال بريد إلكتروني.

- **Method:** `POST`
- **URL:** `https://your-domain.com/api/tenants`
- **Headers:**
  ```http
  Authorization: Bearer <TENANT_PROVISIONING_API_TOKEN>
  Accept: application/json
  Content-Type: application/json
  ```

#### Request Body Parameters:

| الحقل (Field) | النوع (Type) | إلزامي؟ | الوصف (Description) | مثال |
| :--- | :--- | :--- | :--- | :--- |
| `domain` | `string` | **نعم** | اسم الدومين أو الـ Subdomain الخاص بالعميل (يجب أن يكون فريداً). | `client1.ps-web.test` |
| `name` | `string` | **نعم** | اسم العميل / صاحب الحساب. | `أحمد نبيـل` |
| `email` | `string (email)` | **نعم** | البريد الإلكتروني للعميل (سيتم إرسال بيانات الدخول إليه). | `client@example.com` |
| `started_at` | `date` | **نعم** | تاريخ بداية الاشتراك (`YYYY-MM-DD`). | `2026-08-31` |
| `ended_at` | `date` | **نعم** | تاريخ نهاية الاشتراك (`YYYY-MM-DD`). | `2027-08-31` |
| `user_id` | `integer` | **نعم** | معرّف العميل في النظام الخارجي (User ID). | `105` |
| `auto_renew` | `boolean` | **نعم** | هل يتم التجديد تلقائياً؟ (`true` أو `false`). | `true` |
| `branches_limit`| `integer` | *اختياري*| الحد الأقصى لعدد الفروع المسموح بها (الافتراضي: 1). | `3` |

#### Request Payload Example:
```json
{
  "domain": "elite-cafe.ps-web.test",
  "name": "Elite Gaming Cafe",
  "email": "owner@elitecafe.com",
  "started_at": "2026-08-31",
  "ended_at": "2027-08-31",
  "user_id": 42,
  "auto_renew": true,
  "branches_limit": 2
}
```

#### Responses:

##### 202 Accepted (نجاح واستلام الطلب للمعالجة في الخلفية):
```json
{
  "message": "جاري انشاء الموقع الالكتروني وسيتم ارسال بريد الكتروني فور الانتهاء"
}
```

##### 422 Unprocessable Content (خطأ في التحقق من صحة الحقول):
```json
{
  "message": "The domain has already been taken. (and 1 other error)",
  "errors": {
    "domain": [
      "The domain has already been taken."
    ],
    "email": [
      "The email field must be a valid email address."
    ]
  }
}
```

##### 401 Unauthorized (التوكن غير صحيح أو مفقود):
```json
{
  "message": "Unauthorized."
}
```

##### 503 Service Unavailable (التوكن غير مضبوط في ملف .env للسيرفر):
```json
{
  "message": "Tenant provisioning API token is not configured."
}
```

---

### 2.2 حذف تينت (Delete Tenant)

- **Method:** `DELETE`
- **URL:** `https://your-domain.com/api/tenants/{tenantId}`
- **Headers:**
  ```http
  Authorization: Bearer <TENANT_PROVISIONING_API_TOKEN>
  Accept: application/json
  ```

#### Responses:

##### 200 OK (تم الحذف بنجاح):
```json
{
  "message": "Tenant deleted successfully.",
  "tenant_id": "01J...ULID"
}
```

---

## 3. آلية عمل المستخدمين عند الإنشاء (User Provisioning Verification)

عند استدعاء API الإنشاء، يتم تشغيل الوظيفة الخلفية `ProvisionTenantJob` التي تقوم تلقائياً بإنشاء **مستخدمَين (2 Users)** بصلاحية `super_admin` داخل قاعدة بيانات التينت الجديدة:

1. **المستخدم الأول (System Admin - افتراضي):**
   - **الاسم:** `System Admin`
   - **البريد الإلكتروني:** `admin@etech-valley.com`
   - **كلمة المرور الافتراضية:** `Whocandoitnow157#`
   - **الصلاحية (Role):** `super_admin`

2. **المستخدم الثاني (Customer User - حساب العميل):**
   - **الاسم:** القيمة الممررة في حقل `name`
   - **البريد الإلكتروني:** القيمة الممررة في حقل `email`
   - **كلمة المرور الافتراضية:** `12345678`
   - **الصلاحية (Role):** `super_admin`

بالإضافة إلى ذلك:
- يتم إرسال بريد إلكتروني تلقائي للعميل `TenantProvisionedMail` يحتوي على رابط الدخول وبيانات الموقع.
- يتم إطلاق مهمة `GenerateTenantPermissionsJob` لتوليد كافة أذونات Filament Shield في الخلفية.

---

## 4. أمثلة الاستدعاء برمجياً (Code Examples)

### cURL
```bash
curl -X POST "https://your-domain.com/api/tenants" \
  -H "Authorization: Bearer your_secure_random_token_here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "domain": "vip-gaming.ps-web.test",
    "name": "VIP Gaming Lounge",
    "email": "vip@example.com",
    "started_at": "2026-08-31",
    "ended_at": "2027-08-31",
    "user_id": 99,
    "auto_renew": true,
    "branches_limit": 1
  }'
```

### PHP (Laravel Guzzle / Http Client)
```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken(config('services.tenant_provisioning.token'))
    ->acceptJson()
    ->post('https://your-domain.com/api/tenants', [
        'domain' => 'vip-gaming.ps-web.test',
        'name' => 'VIP Gaming Lounge',
        'email' => 'vip@example.com',
        'started_at' => '2026-08-31',
        'ended_at' => '2027-08-31',
        'user_id' => 99,
        'auto_renew' => true,
        'branches_limit' => 1,
    ]);

if ($response->successful()) {
    $result = $response->json();
    // {"message": "جاري انشاء الموقع الالكتروني وسيتم ارسال بريد الكتروني فور الانتهاء"}
}
```

### Node.js / Axios (JavaScript)
```javascript
const axios = require('axios');

async function createTenant() {
  try {
    const response = await axios.post('https://your-domain.com/api/tenants', {
      domain: 'vip-gaming.ps-web.test',
      name: 'VIP Gaming Lounge',
      email: 'vip@example.com',
      started_at: '2026-08-31',
      ended_at: '2027-08-31',
      user_id: 99,
      auto_renew: true,
      branches_limit: 1
    }, {
      headers: {
        'Authorization': 'Bearer your_secure_random_token_here',
        'Accept': 'application/json'
      }
    });

    console.log(response.status, response.data);
  } catch (error) {
    console.error('Error creating tenant:', error.response?.data || error.message);
  }
}
```

### Python (requests)
```python
import requests

url = "https://your-domain.com/api/tenants"
headers = {
    "Authorization": "Bearer your_secure_random_token_here",
    "Accept": "application/json",
    "Content-Type": "application/json"
}
payload = {
    "domain": "vip-gaming.ps-web.test",
    "name": "VIP Gaming Lounge",
    "email": "vip@example.com",
    "started_at": "2026-08-31",
    "ended_at": "2027-08-31",
    "user_id": 99,
    "auto_renew": True,
    "branches_limit": 1
}

response = requests.post(url, json=payload, headers=headers)
print(response.status_code, response.json())
```
