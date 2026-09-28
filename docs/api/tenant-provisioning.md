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

| الحقل (Field)         | النوع (Type)     | إلزامي؟   | الوصف (Description)                                              | مثال                  |
| :-------------------- | :--------------- | :-------- | :--------------------------------------------------------------- | :-------------------- |
| `domain`              | `string`         | **نعم**   | اسم الدومين أو الـ Subdomain الخاص بالعميل (يجب أن يكون فريداً). | `client1.ps-web.test` |
| `name`                | `string`         | **نعم**   | اسم العميل / صاحب الحساب.                                        | `أحمد نبيـل`          |
| `email`               | `string (email)` | **نعم**   | البريد الإلكتروني للعميل (سيتم إرسال بيانات الدخول إليه).        | `client@example.com`  |
| `started_at`          | `date`           | **نعم**   | تاريخ بداية الاشتراك (`YYYY-MM-DD`).                             | `2026-08-31`          |
| `ended_at`            | `date`           | **نعم**   | تاريخ نهاية الاشتراك (`YYYY-MM-DD`).                             | `2027-08-31`          |
| `user_id`             | `integer`        | **نعم**   | معرّف العميل في النظام الخارجي (User ID).                        | `105`                 |
| `auto_renew`          | `boolean`        | **نعم**   | هل يتم التجديد تلقائياً؟ (`true` أو `false`).                    | `true`                |
| `branches_limit`      | `integer`        | _اختياري_ | الحد الأقصى لعدد الفروع المسموح بها (الافتراضي: 1).              | `3`                   |
| `has_ai_subscription` | `boolean`        | _اختياري_ | تفعيل اشتراك المساعد الذكي AI (الافتراضي: `true`).               | `true`                |
| `ai_requests_limit`   | `integer`        | _اختياري_ | الحد اليومي لعدد رسائل المساعد الذكي (الافتراضي: `10`).          | `10`                  |

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
    "branches_limit": 2,
    "has_ai_subscription": true,
    "ai_requests_limit": 10
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
        "domain": ["The domain has already been taken."],
        "email": ["The email field must be a valid email address."]
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

### 2.3 تجديد اشتراك تينت (Renew Tenant)

تُستخدم هذه النقطة لتحديث تاريخ انتهاء اشتراك تينت موجود وإعادة تفعيله. يمكن التجديد قبل انتهاء الاشتراك أو خلال فترة السماح بعد الانتهاء. فترة السماح الافتراضية هي **3 أيام**، ويمكن ضبطها من خلال المتغير `TENANT_EXPIRATION_GRACE_DAYS`.

> يجب إرسال تاريخ انتهاء جديد في المستقبل، ولا يمكن أن يكون أقدم من تاريخ الانتهاء الحالي. إعادة إرسال نفس الطلب بنفس تاريخ الانتهاء مسموح بها ما دام التاريخ لا يزال في المستقبل.

- **Method:** `POST`
- **URL:** `https://your-domain.com/api/tenants/{tenantId}/renew`
- **Path Parameter:** استبدل `{tenantId}` بمعرّف التينت (Tenant ID / ULID).
- **Headers:**
    ```http
    Authorization: Bearer <TENANT_PROVISIONING_API_TOKEN>
    Accept: application/json
    Content-Type: application/json
    ```

#### Request Body Parameters:

| الحقل (Field) | النوع (Type) | إلزامي؟ | الوصف (Description)                                                                                                                       | مثال                   |
| :------------ | :----------- | :------ | :---------------------------------------------------------------------------------------------------------------------------------------- | :--------------------- |
| `ended_at`    | `date`       | **نعم** | تاريخ انتهاء الاشتراك الجديد. يجب أن يكون في المستقبل وألا يقل عن تاريخ الانتهاء الحالي. يُفضّل إرساله بصيغة ISO 8601 مع المنطقة الزمنية. | `2027-08-31T21:00:00Z` |

#### Request Payload Example:

```json
{
    "ended_at": "2027-08-31T21:00:00Z"
}
```

#### cURL Example:

```bash
curl -X POST "https://your-domain.com/api/tenants/01J...ULID/renew" \
  -H "Authorization: Bearer your_secure_random_token_here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "ended_at": "2027-08-31T21:00:00Z"
  }'
```

#### Responses:

##### 200 OK (تم التجديد وإعادة تفعيل التينت):

يتم حفظ التاريخ بالتوقيت العالمي `UTC` وإرجاعه بصيغة ISO 8601، كما يتم ضبط `is_active` إلى `true`.

```json
{
    "tenant_id": "01J...ULID",
    "ended_at": "2027-08-31T21:00:00+00:00",
    "is_active": true
}
```

##### 422 Unprocessable Content (بيانات التجديد غير صالحة):

يحدث ذلك إذا كان `ended_at` مفقودًا، أو ليس تاريخًا صالحًا، أو ليس في المستقبل، أو سيؤدي إلى تقصير مدة الاشتراك الحالية.

```json
{
    "message": "The renewal cannot shorten the current subscription.",
    "errors": {
        "ended_at": ["The renewal cannot shorten the current subscription."]
    }
}
```

##### 401 Unauthorized (التوكن غير صحيح أو مفقود):

```json
{
    "message": "Unauthorized."
}
```

##### 404 Not Found (التينت غير موجود):

##### 409 Conflict (لا يمكن تنفيذ التجديد مؤقتًا):

يحدث ذلك إذا كان التينت لا يزال قيد الإنشاء، أو إذا كانت هناك عملية أخرى تمسك بقفل دورة حياة التينت. في حالة انشغال التينت يمكن إعادة المحاولة بعد وقت قصير.

```json
{
    "message": "Tenant is busy. Retry the renewal shortly."
}
```

##### 410 Gone (لم يعد التجديد متاحًا):

يحدث ذلك إذا بدأت عملية حذف التينت، أو انتهت فترة السماح المحسوبة من تاريخ `ended_at` الحالي. عند الوصول إلى نهاية فترة السماح نفسها لا يُسمح بالتجديد.

```json
{
    "message": "The renewal grace period has ended."
}
```

---

### 2.4 إيقاف وتفعيل التينت (Suspend / Activate Tenant)

يستخدم النظام الأساسي هذين المسارين لتغيير حالة الوصول فقط، دون تعديل تاريخ انتهاء الاشتراك أو إنشاء أي حركة مالية.

```http
POST /api/tenants/{tenantId}/suspend
POST /api/tenants/{tenantId}/activate
```

نفس ترويسات المصادقة و`Idempotency-Key` المستخدمة في باقي Lifecycle APIs مطلوبة. لا يوجد Request Body.

الإيقاف يضبط `is_active=false` والحالة `suspended` ويرسل Webhook من نوع `tenant.suspended`. التفعيل مسموح فقط لتينانت جاهز وغير منتهي، ويضبط `is_active=true` والحالة `ready` ويرسل `tenant.activated`. يجب تجديد الاشتراك المنتهي قبل تفعيله.

```json
{
    "tenant_id": "01J...ULID",
    "external_subscription_id": "online-instance:481",
    "status": "suspended",
    "is_active": false
}
```

قد ترجع العملية `404 TENANT_NOT_FOUND` أو `409 TENANT_BUSY` أو `409 TENANT_NOT_READY` أو `409 SUBSCRIPTION_EXPIRED` أو `410 TENANT_DELETION_STARTED` حسب حالة التينانت.

---

### 2.5 تحديث مزايا وحدود التينت (Update Tenant Entitlements)

تُستخدم هذه النقطة بعد نجاح الترقية أو شراء إضافة على الموقع الأساسي لتحديث القيم النهائية الحالية المسموح بها للتينت، مثل عدد الفروع وتفعيل AI Chat وحد الرسائل اليومي.

> هذا الـ API لا يحفظ تاريخ عمليات الشراء أو الفواتير ولا يستقبل رقم عملية دفع. يظل الموقع الأساسي هو مصدر الحقيقة للمدفوعات والـ history، ويرسل إلى هذا النظام **القيمة النهائية** بعد كل تغيير.

- **Method:** `PATCH`
- **URL:** `https://your-domain.com/api/tenants/{tenantId}/entitlements`
- **Path Parameter:** استبدل `{tenantId}` بمعرّف التينت (Tenant ID / ULID).
- **Headers:**
    ```http
    Authorization: Bearer <TENANT_PROVISIONING_API_TOKEN>
    Accept: application/json
    Content-Type: application/json
    ```

#### Request Body Parameters:

يجب إرسال حقل واحد على الأقل. الحقول غير المرسلة تحتفظ بقيمتها الحالية.

| الحقل (Field)         | النوع (Type) | إلزامي؟ | الوصف (Description)                                          | مثال   |
| :-------------------- | :----------- | :------ | :----------------------------------------------------------- | :----- |
| `branches_limit`      | `integer`    | اختياري | العدد النهائي للفروع المسموح بها. الحد الأدنى `1`.           | `3`    |
| `has_ai_subscription` | `boolean`    | اختياري | تفعيل أو إيقاف AI Chat.                                      | `true` |
| `ai_requests_limit`   | `integer`    | اختياري | العدد النهائي لرسائل AI المسموح بها يوميًا. الحد الأدنى `0`. | `20`   |

#### مثال ترقية كاملة:

```json
{
    "branches_limit": 3,
    "has_ai_subscription": true,
    "ai_requests_limit": 20
}
```

#### مثال زيادة حد رسائل AI فقط:

```json
{
    "ai_requests_limit": 20
}
```

#### cURL Example:

```bash
curl -X PATCH "https://your-domain.com/api/tenants/01J...ULID/entitlements" \
  -H "Authorization: Bearer your_secure_random_token_here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "branches_limit": 3,
    "has_ai_subscription": true,
    "ai_requests_limit": 20
  }'
```

يجب إرسال القيم النهائية وليس مقدار الزيادة. على سبيل المثال، لترقية الحد من 10 إلى 20 رسالة أرسل `20`، وليس `10`. لذلك يمكن للموقع الأساسي إعادة إرسال نفس الطلب بأمان دون مضاعفة الحد.

#### سلوك التحديث:

- يسري حد الفروع الجديد فورًا عند محاولة إنشاء فرع جديد.
- تقليل `branches_limit` لا يحذف الفروع الموجودة، ولكنه يمنع إنشاء فروع إضافية حتى يصبح العدد الحالي أقل من الحد.
- تفعيل `has_ai_subscription` يسمح باستخدام AI Chat فورًا.
- تغيير `ai_requests_limit` لا يصفّر `ai_requests_today`. إذا استُخدمت 8 رسائل وتم رفع الحد إلى 20، يتبقى 12 رسالة في اليوم نفسه.
- إيقاف AI Chat لا يغيّر حد الرسائل المخزن إلا إذا تم إرسال `ai_requests_limit` أيضًا.
- هذا التحديث لا يجدّد تاريخ الاشتراك ولا يغيّر `started_at` أو `ended_at` أو `is_active`.

#### Responses:

##### 200 OK (تم تحديث المزايا):

```json
{
    "tenant_id": "01J...ULID",
    "branches_limit": 3,
    "has_ai_subscription": true,
    "ai_requests_limit": 20
}
```

##### 422 Unprocessable Content (الطلب فارغ أو القيم غير صالحة):

```json
{
    "message": "The branches limit field must be at least 1.",
    "errors": {
        "branches_limit": ["The branches limit field must be at least 1."]
    }
}
```

##### 401 Unauthorized (التوكن غير صحيح أو مفقود):

```json
{
    "message": "Unauthorized."
}
```

##### 404 Not Found (التينت غير موجود):

##### 409 Conflict (التينت قيد الإنشاء أو مشغول بعملية أخرى):

```json
{
    "message": "Tenant is busy. Retry the entitlement update shortly."
}
```

##### 410 Gone (بدأ حذف التينت):

```json
{
    "message": "Tenant deletion has started."
}
```

---

### 2.6 حذف تينت (Delete Tenant)

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

1. **المستخدم (Customer User - حساب العميل):**
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
const axios = require("axios");

async function createTenant() {
    try {
        const response = await axios.post(
            "https://your-domain.com/api/tenants",
            {
                domain: "vip-gaming.ps-web.test",
                name: "VIP Gaming Lounge",
                email: "vip@example.com",
                started_at: "2026-08-31",
                ended_at: "2027-08-31",
                user_id: 99,
                auto_renew: true,
                branches_limit: 1,
            },
            {
                headers: {
                    Authorization: "Bearer your_secure_random_token_here",
                    Accept: "application/json",
                },
            },
        );

        console.log(response.status, response.data);
    } catch (error) {
        console.error(
            "Error creating tenant:",
            error.response?.data || error.message,
        );
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
