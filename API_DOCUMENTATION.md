# وثائق API للعملاء

## نظرة عامة

هذا API مخصص لإدارة العملاء الذين يسجلون الدخول من تطبيق موبايل عبر Google OAuth من Firebase.

**Base URL:** `http://your-domain.com/api`

**Authentication:** Bearer Token (Laravel Sanctum)

---

## جدول المحتويات

1. [التسجيل](#التسجيل)
2. [تسجيل الدخول](#تسجيل-الدخول)
3. [الملف الشخصي](#الملف-الشخصي)
4. [تحديث الملف الشخصي](#تحديث-الملف-الشخصي)
5. [تسجيل الخروج](#تسجيل-الخروج)
6. [تحديث التوكن](#تحديث-التوكن)
7. [أكواد الأخطاء](#أكواد-الأخطاء)

---

## التسجيل

إنشاء حساب عميل جديد من Firebase.

### Endpoint

```
POST /api/clients/register
```

### Headers

```
Content-Type: application/json
Accept: application/json
```

### Request Body

```json
{
  "firebase_uid": "string (required, unique)",
  "name": "string (required, max:255)",
  "email": "string (required, email, unique)",
  "phone": "string (optional, max:20)",
  "photo_url": "string (optional, url)",
  "provider": "string (required, in:google,facebook,apple)",
  "provider_id": "string (optional)",
  "device_token": "string (optional)"
}
```

### مثال على الطلب

```json
{
  "firebase_uid": "abc123xyz789",
  "name": "أحمد محمد",
  "email": "ahmed@example.com",
  "phone": "07701234567",
  "photo_url": "https://example.com/photo.jpg",
  "provider": "google",
  "provider_id": "google_user_id_123",
  "device_token": "fcm_device_token_here"
}
```

### Response (Success - 201)

```json
{
  "success": true,
  "message": "تم تسجيل العميل بنجاح",
  "data": {
    "client": {
      "id": 1,
      "firebase_uid": "abc123xyz789",
      "name": "أحمد محمد",
      "email": "ahmed@example.com",
      "phone": "07701234567",
      "photo_url": "https://example.com/photo.jpg",
      "provider": "google",
      "provider_id": "google_user_id_123",
      "is_active": true,
      "last_login_at": "2025-12-10T15:30:00.000000Z",
      "created_at": "2025-12-10T15:30:00.000000Z",
      "updated_at": "2025-12-10T15:30:00.000000Z"
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz1234567890",
    "token_type": "Bearer"
  }
}
```

### Response (Error - 422)

```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "firebase_uid": ["معرف Firebase مطلوب"],
    "email": ["البريد الإلكتروني مستخدم مسبقاً"]
  }
}
```

---

## تسجيل الدخول

تسجيل دخول عميل موجود باستخدام Firebase UID.

### Endpoint

```
POST /api/clients/login
```

### Headers

```
Content-Type: application/json
Accept: application/json
```

### Request Body

```json
{
  "firebase_uid": "string (required)",
  "device_token": "string (optional)"
}
```

### مثال على الطلب

```json
{
  "firebase_uid": "abc123xyz789",
  "device_token": "fcm_device_token_here"
}
```

### Response (Success - 200)

```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح",
  "data": {
    "client": {
      "id": 1,
      "firebase_uid": "abc123xyz789",
      "name": "أحمد محمد",
      "email": "ahmed@example.com",
      "phone": "07701234567",
      "photo_url": "https://example.com/photo.jpg",
      "provider": "google",
      "is_active": true,
      "last_login_at": "2025-12-10T16:00:00.000000Z",
      "created_at": "2025-12-10T15:30:00.000000Z",
      "updated_at": "2025-12-10T16:00:00.000000Z"
    },
    "token": "2|newtoken1234567890abcdefghijklmnop",
    "token_type": "Bearer"
  }
}
```

### Response (Error - 404)

```json
{
  "success": false,
  "message": "العميل غير موجود. يرجى التسجيل أولاً."
}
```

### Response (Error - 403)

```json
{
  "success": false,
  "message": "حسابك غير نشط. يرجى التواصل مع الدعم."
}
```

---

## الملف الشخصي

الحصول على بيانات العميل الحالي.

### Endpoint

```
GET /api/clients/profile
```

### Headers

```
Authorization: Bearer {token}
Accept: application/json
```

### Response (Success - 200)

```json
{
  "success": true,
  "data": {
    "client": {
      "id": 1,
      "firebase_uid": "abc123xyz789",
      "name": "أحمد محمد",
      "email": "ahmed@example.com",
      "phone": "07701234567",
      "photo_url": "https://example.com/photo.jpg",
      "provider": "google",
      "provider_id": "google_user_id_123",
      "is_active": true,
      "last_login_at": "2025-12-10T16:00:00.000000Z",
      "created_at": "2025-12-10T15:30:00.000000Z",
      "updated_at": "2025-12-10T16:00:00.000000Z"
    }
  }
}
```

### Response (Error - 401)

```json
{
  "message": "Unauthenticated."
}
```

---

## تحديث الملف الشخصي

تحديث بيانات العميل الحالي.

### Endpoint

```
PUT /api/clients/profile
```

### Headers

```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### Request Body

جميع الحقول اختيارية:

```json
{
  "name": "string (optional, max:255)",
  "phone": "string (optional, max:20)",
  "photo_url": "string (optional, url)",
  "device_token": "string (optional)"
}
```

### مثال على الطلب

```json
{
  "name": "أحمد محمد علي",
  "phone": "07701234568",
  "photo_url": "https://example.com/new-photo.jpg",
  "device_token": "new_fcm_device_token"
}
```

### Response (Success - 200)

```json
{
  "success": true,
  "message": "تم تحديث البيانات بنجاح",
  "data": {
    "client": {
      "id": 1,
      "firebase_uid": "abc123xyz789",
      "name": "أحمد محمد علي",
      "email": "ahmed@example.com",
      "phone": "07701234568",
      "photo_url": "https://example.com/new-photo.jpg",
      "provider": "google",
      "is_active": true,
      "last_login_at": "2025-12-10T16:00:00.000000Z",
      "created_at": "2025-12-10T15:30:00.000000Z",
      "updated_at": "2025-12-10T16:30:00.000000Z"
    }
  }
}
```

### Response (Error - 422)

```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "photo_url": ["رابط الصورة غير صحيح"]
  }
}
```

---

## تسجيل الخروج

تسجيل خروج العميل وحذف التوكن الحالي.

### Endpoint

```
POST /api/clients/logout
```

### Headers

```
Authorization: Bearer {token}
Accept: application/json
```

### Response (Success - 200)

```json
{
  "success": true,
  "message": "تم تسجيل الخروج بنجاح"
}
```

### Response (Error - 401)

```json
{
  "message": "Unauthenticated."
}
```

---

## تحديث التوكن

حذف جميع التوكنات القديمة وإنشاء توكن جديد.

### Endpoint

```
POST /api/clients/refresh-token
```

### Headers

```
Authorization: Bearer {token}
Accept: application/json
```

### Response (Success - 200)

```json
{
  "success": true,
  "message": "تم تحديث التوكن بنجاح",
  "data": {
    "token": "3|newrefreshedtoken1234567890abcdefgh",
    "token_type": "Bearer"
  }
}
```

### Response (Error - 401)

```json
{
  "message": "Unauthenticated."
}
```

---

## أكواد الأخطاء

| الكود | المعنى | الوصف |
|------|--------|-------|
| 200 | OK | الطلب نجح |
| 201 | Created | تم إنشاء المورد بنجاح |
| 401 | Unauthorized | غير مصرح - التوكن غير صحيح أو منتهي |
| 403 | Forbidden | محظور - الحساب غير نشط |
| 404 | Not Found | غير موجود - العميل غير موجود |
| 422 | Validation Error | خطأ في التحقق من البيانات |
| 500 | Internal Server Error | خطأ في الخادم |

---

## أمثلة على الاستخدام

### JavaScript (Fetch API)

#### التسجيل

```javascript
const registerClient = async (clientData) => {
  const response = await fetch('http://your-domain.com/api/clients/register', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify(clientData)
  });

  const data = await response.json();
  if (data.success) {
    // حفظ التوكن
    localStorage.setItem('token', data.data.token);
    return data.data.client;
  } else {
    throw new Error(data.message);
  }
};
```

#### تسجيل الدخول

```javascript
const loginClient = async (firebaseUid, deviceToken = null) => {
  const response = await fetch('http://your-domain.com/api/clients/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({
      firebase_uid: firebaseUid,
      device_token: deviceToken
    })
  });

  const data = await response.json();
  if (data.success) {
    localStorage.setItem('token', data.data.token);
    return data.data.client;
  } else {
    throw new Error(data.message);
  }
};
```

#### الحصول على الملف الشخصي

```javascript
const getProfile = async () => {
  const token = localStorage.getItem('token');
  const response = await fetch('http://your-domain.com/api/clients/profile', {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });

  const data = await response.json();
  if (data.success) {
    return data.data.client;
  } else {
    throw new Error(data.message);
  }
};
```

#### تحديث الملف الشخصي

```javascript
const updateProfile = async (profileData) => {
  const token = localStorage.getItem('token');
  const response = await fetch('http://your-domain.com/api/clients/profile', {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify(profileData)
  });

  const data = await response.json();
  if (data.success) {
    return data.data.client;
  } else {
    throw new Error(data.message);
  }
};
```

#### تسجيل الخروج

```javascript
const logout = async () => {
  const token = localStorage.getItem('token');
  const response = await fetch('http://your-domain.com/api/clients/logout', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });

  const data = await response.json();
  if (data.success) {
    localStorage.removeItem('token');
    return true;
  } else {
    throw new Error(data.message);
  }
};
```

### cURL

#### التسجيل

```bash
curl -X POST http://your-domain.com/api/clients/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "firebase_uid": "abc123xyz789",
    "name": "أحمد محمد",
    "email": "ahmed@example.com",
    "phone": "07701234567",
    "photo_url": "https://example.com/photo.jpg",
    "provider": "google",
    "provider_id": "google_user_id_123"
  }'
```

#### تسجيل الدخول

```bash
curl -X POST http://your-domain.com/api/clients/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "firebase_uid": "abc123xyz789",
    "device_token": "fcm_device_token_here"
  }'
```

#### الحصول على الملف الشخصي

```bash
curl -X GET http://your-domain.com/api/clients/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

---

## ملاحظات مهمة

1. **التوكن (Token):** يجب إرسال التوكن في header `Authorization` بصيغة `Bearer {token}` لجميع الطلبات المحمية.

2. **Firebase UID:** يجب أن يكون `firebase_uid` فريداً لكل عميل. يتم الحصول عليه من Firebase Authentication.

3. **المزود (Provider):** القيم المدعومة هي: `google`, `facebook`, `apple`.

4. **Device Token:** يُستخدم لإرسال الإشعارات للعميل. يمكن تحديثه في أي وقت.

5. **الحالة (is_active):** يمكن للمدير تعطيل حساب عميل من لوحة التحكم. العميل غير النشط لا يمكنه تسجيل الدخول.

6. **آخر تسجيل دخول:** يتم تحديث `last_login_at` تلقائياً عند تسجيل الدخول.

7. **التوكنات:** يمكن للعميل الحصول على عدة توكنات (لأجهزة مختلفة). عند استخدام `refresh-token`، يتم حذف جميع التوكنات القديمة.

---

## الدعم

للمساعدة والدعم، يرجى التواصل مع فريق التطوير.

