# دليل استخدام API في تطبيق الموبايل

## نظرة عامة

هذا الدليل يشرح كيفية دمج API الخاص بالعملاء في تطبيق الموبايل (Flutter/React Native/Android/iOS). يمكنك إعطاء هذا الدليل لأي AI ليقوم بتطبيقه في التطبيق.

---

## Base URL

```
https://your-domain.com/api
```

**ملاحظة:** استبدل `your-domain.com` بعنوان السيرفر الفعلي.

---

## 1. التسجيل (Register)

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
  "firebase_uid": "string (required)",
  "name": "string (required)",
  "email": "string (required, email)",
  "phone": "string (optional)",
  "photo_url": "string (optional, url)",
  "provider": "google|facebook|apple (required)",
  "provider_id": "string (optional)",
  "device_token": "string (optional)"
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
      "firebase_uid": "abc123",
      "name": "أحمد محمد",
      "email": "ahmed@example.com",
      "status": "pending",
      "activation_expires_at": null,
      ...
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz",
    "token_type": "Bearer"
  }
}
```

### Response (Error - 422)
```json
{
  "success": false,
  "message": "خطأ في التحقق من البيانات",
  "errors": {
    "email": ["البريد الإلكتروني مستخدم مسبقاً"]
  }
}
```

### ملاحظات مهمة:
- بعد التسجيل، الحالة ستكون `pending` (في الانتظار)
- احفظ `token` في التطبيق (SharedPreferences/SecureStorage)
- العميل لا يمكنه تسجيل الدخول حتى يتم تفعيله من لوحة التحكم

---

## 2. تسجيل الدخول (Login)

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

### Response (Success - 200)
```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح",
  "data": {
    "client": {
      "id": 1,
      "status": "active",
      "activation_expires_at": "2026-12-10T00:00:00.000000Z",
      ...
    },
    "token": "2|newtoken123456",
    "token_type": "Bearer"
  }
}
```

### Response (Error - 403) - حساب محظور
```json
{
  "success": false,
  "message": "حسابك محظور. يرجى التواصل مع الدعم."
}
```

### Response (Error - 403) - في الانتظار
```json
{
  "success": false,
  "message": "حسابك في قائمة الانتظار. يرجى انتظار التفعيل من الإدارة."
}
```

### Response (Error - 403) - غير مفعل
```json
{
  "success": false,
  "message": "حسابك غير مفعل أو انتهت مدة التفعيل. يرجى التواصل مع الدعم."
}
```

### ملاحظات مهمة:
- إذا كان `status: "pending"`، اعرض رسالة للمستخدم بأنه في الانتظار
- إذا كان `status: "banned"`، اعرض رسالة بأن الحساب محظور
- إذا كان `status: "active"` لكن `activation_expires_at` منتهي، اعرض رسالة بأن المدة انتهت

---

## 3. الحصول على حالة العميل (Get Status)

### Endpoint
```
GET /api/clients/status
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
    "status": "active",
    "activation_expires_at": "2026-12-10T00:00:00.000000Z",
    "is_expired": false,
    "is_active": true,
    "is_pending": false,
    "is_banned": false
  }
}
```

### ملاحظات مهمة:
- استخدم هذا الـ endpoint للتحقق من الحالة بشكل دوري
- إذا كان `is_expired: true`، اعرض رسالة للمستخدم
- إذا كان `is_banned: true`، امنع الوصول للتطبيق

---

## 4. الملف الشخصي (Profile)

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
      "name": "أحمد محمد",
      "email": "ahmed@example.com",
      "phone": "07701234567",
      "photo_url": "https://example.com/photo.jpg",
      "status": "active",
      "activation_expires_at": "2026-12-10T00:00:00.000000Z",
      ...
    }
  }
}
```

---

## 5. تحديث الملف الشخصي (Update Profile)

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

### Request Body (جميع الحقول اختيارية)
```json
{
  "name": "string (optional)",
  "phone": "string (optional)",
  "photo_url": "string (optional, url)",
  "device_token": "string (optional)"
}
```

---

## 6. تسجيل الخروج (Logout)

### Endpoint
```
POST /api/clients/logout
```

### Headers
```
Authorization: Bearer {token}
Accept: application/json
```

---

## 7. تحديث التوكن (Refresh Token)

### Endpoint
```
POST /api/clients/refresh-token
```

### Headers
```
Authorization: Bearer {token}
Accept: application/json
```

---

## حالات العميل (Status)

### 1. pending (في الانتظار)
- الحالة الافتراضية عند التسجيل
- العميل لا يمكنه استخدام التطبيق
- اعرض شاشة "في انتظار التفعيل"

### 2. active (مفعل)
- العميل مفعل ويمكنه استخدام التطبيق
- تحقق من `activation_expires_at` - إذا كان منتهياً، الحساب غير نشط
- اعرض التطبيق بشكل طبيعي

### 3. banned (محظور)
- العميل محظور ولا يمكنه استخدام التطبيق
- اعرض شاشة "حسابك محظور" وامنع الوصول

---

## Push Notifications (الإشعارات)

### استقبال الإشعارات عند تغيير الحالة

عند تغيير الحالة من لوحة التحكم، سيتم إرسال إشعار push notification يحتوي على:

```json
{
  "notification": {
    "title": "تم تفعيل حسابك",
    "body": "تم تفعيل حسابك بنجاح لمدة 12 شهر..."
  },
  "data": {
    "type": "status_change",
    "status": "active",
    "activation_expires_at": "2026-12-10T00:00:00.000000Z",
    "is_expired": false,
    "is_active": true,
    "is_pending": false,
    "is_banned": false
  }
}
```

### كيفية التعامل مع الإشعار:

1. **عند استقبال إشعار `type: "status_change"`:**
   - استدعِ `GET /api/clients/status` لتحديث الحالة
   - حدّث الواجهة حسب الحالة الجديدة
   - إذا أصبح `active`، اسمح بالوصول
   - إذا أصبح `banned`، امنع الوصول

2. **عند فتح الإشعار:**
   - إذا كان `status: "active"`، افتح التطبيق بشكل طبيعي
   - إذا كان `status: "banned"`، اعرض شاشة الحظر
   - إذا كان `status: "pending"`، اعرض شاشة الانتظار

---

## أمثلة كود

### Flutter/Dart

```dart
class ApiService {
  static const String baseUrl = 'https://your-domain.com/api';
  String? token;

  // حفظ التوكن
  Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', token);
    this.token = token;
  }

  // جلب التوكن
  Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    token = prefs.getString('auth_token');
    return token;
  }

  // التسجيل
  Future<Map<String, dynamic>> register(Map<String, dynamic> data) async {
    final response = await http.post(
      Uri.parse('$baseUrl/clients/register'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: jsonEncode(data),
    );

    final responseData = jsonDecode(response.body);
    
    if (response.statusCode == 201 && responseData['success']) {
      await saveToken(responseData['data']['token']);
      return responseData['data'];
    } else {
      throw Exception(responseData['message'] ?? 'خطأ في التسجيل');
    }
  }

  // تسجيل الدخول
  Future<Map<String, dynamic>> login(String firebaseUid, {String? deviceToken}) async {
    final response = await http.post(
      Uri.parse('$baseUrl/clients/login'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: jsonEncode({
        'firebase_uid': firebaseUid,
        'device_token': deviceToken,
      }),
    );

    final responseData = jsonDecode(response.body);
    
    if (response.statusCode == 200 && responseData['success']) {
      await saveToken(responseData['data']['token']);
      return responseData['data'];
    } else {
      throw Exception(responseData['message'] ?? 'خطأ في تسجيل الدخول');
    }
  }

  // الحصول على الحالة
  Future<Map<String, dynamic>> getStatus() async {
    final token = await getToken();
    if (token == null) throw Exception('غير مصرح');

    final response = await http.get(
      Uri.parse('$baseUrl/clients/status'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      },
    );

    final responseData = jsonDecode(response.body);
    
    if (response.statusCode == 200 && responseData['success']) {
      return responseData['data'];
    } else {
      throw Exception(responseData['message'] ?? 'خطأ في جلب الحالة');
    }
  }

  // الملف الشخصي
  Future<Map<String, dynamic>> getProfile() async {
    final token = await getToken();
    if (token == null) throw Exception('غير مصرح');

    final response = await http.get(
      Uri.parse('$baseUrl/clients/profile'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      },
    );

    final responseData = jsonDecode(response.body);
    
    if (response.statusCode == 200 && responseData['success']) {
      return responseData['data']['client'];
    } else {
      throw Exception(responseData['message'] ?? 'خطأ في جلب الملف الشخصي');
    }
  }
}
```

### React Native/JavaScript

```javascript
const API_BASE_URL = 'https://your-domain.com/api';

class ApiService {
  constructor() {
    this.token = null;
  }

  // حفظ التوكن
  async saveToken(token) {
    await AsyncStorage.setItem('auth_token', token);
    this.token = token;
  }

  // جلب التوكن
  async getToken() {
    if (!this.token) {
      this.token = await AsyncStorage.getItem('auth_token');
    }
    return this.token;
  }

  // التسجيل
  async register(data) {
    const response = await fetch(`${API_BASE_URL}/clients/register`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(data),
    });

    const responseData = await response.json();

    if (response.ok && responseData.success) {
      await this.saveToken(responseData.data.token);
      return responseData.data;
    } else {
      throw new Error(responseData.message || 'خطأ في التسجيل');
    }
  }

  // تسجيل الدخول
  async login(firebaseUid, deviceToken = null) {
    const response = await fetch(`${API_BASE_URL}/clients/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        firebase_uid: firebaseUid,
        device_token: deviceToken,
      }),
    });

    const responseData = await response.json();

    if (response.ok && responseData.success) {
      await this.saveToken(responseData.data.token);
      return responseData.data;
    } else {
      throw new Error(responseData.message || 'خطأ في تسجيل الدخول');
    }
  }

  // الحصول على الحالة
  async getStatus() {
    const token = await this.getToken();
    if (!token) throw new Error('غير مصرح');

    const response = await fetch(`${API_BASE_URL}/clients/status`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
    });

    const responseData = await response.json();

    if (response.ok && responseData.success) {
      return responseData.data;
    } else {
      throw new Error(responseData.message || 'خطأ في جلب الحالة');
    }
  }

  // الملف الشخصي
  async getProfile() {
    const token = await this.getToken();
    if (!token) throw new Error('غير مصرح');

    const response = await fetch(`${API_BASE_URL}/clients/profile`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
      },
    });

    const responseData = await response.json();

    if (response.ok && responseData.success) {
      return responseData.data.client;
    } else {
      throw new Error(responseData.message || 'خطأ في جلب الملف الشخصي');
    }
  }
}
```

---

## خطوات التنفيذ في التطبيق

### 1. عند فتح التطبيق:

```dart
// Flutter Example
void checkAuthStatus() async {
  final token = await getToken();
  
  if (token == null) {
    // لا يوجد token - اعرض شاشة تسجيل الدخول
    navigateToLogin();
    return;
  }

  try {
    // تحقق من الحالة
    final status = await apiService.getStatus();
    
    if (status['is_banned']) {
      // حساب محظور
      navigateToBannedScreen();
    } else if (status['is_pending']) {
      // في الانتظار
      navigateToPendingScreen();
    } else if (!status['is_active'] || status['is_expired']) {
      // غير مفعل أو منتهي
      navigateToInactiveScreen();
    } else {
      // مفعل - افتح التطبيق
      navigateToHome();
    }
  } catch (e) {
    // خطأ في الاتصال - حاول تسجيل الدخول مرة أخرى
    navigateToLogin();
  }
}
```

### 2. عند تسجيل الدخول:

```dart
void handleLogin(String firebaseUid) async {
  try {
    final result = await apiService.login(firebaseUid, deviceToken: fcmToken);
    final client = result['client'];
    
    // تحقق من الحالة
    if (client['status'] == 'pending') {
      showMessage('حسابك في قائمة الانتظار. يرجى انتظار التفعيل.');
      navigateToPendingScreen();
    } else if (client['status'] == 'banned') {
      showMessage('حسابك محظور. يرجى التواصل مع الدعم.');
      navigateToBannedScreen();
    } else if (client['status'] == 'active') {
      // تحقق من انتهاء المدة
      final expiresAt = DateTime.parse(client['activation_expires_at']);
      if (expiresAt.isBefore(DateTime.now())) {
        showMessage('انتهت مدة التفعيل. يرجى التواصل مع الدعم.');
        navigateToInactiveScreen();
      } else {
        // كل شيء جيد - افتح التطبيق
        navigateToHome();
      }
    }
  } catch (e) {
    if (e.toString().contains('غير موجود')) {
      // العميل غير موجود - قم بالتسجيل
      await handleRegister(firebaseUid);
    } else {
      showError(e.toString());
    }
  }
}
```

### 3. عند استقبال Push Notification:

```dart
void handlePushNotification(Map<String, dynamic> notification) {
  final data = notification['data'];
  
  if (data['type'] == 'status_change') {
    // تحديث الحالة
    final status = data['status'];
    
    if (status == 'active') {
      // تم التفعيل - حدّث الحالة وافتح التطبيق
      updateClientStatus(data);
      navigateToHome();
    } else if (status == 'banned') {
      // تم الحظر - اعرض شاشة الحظر
      updateClientStatus(data);
      navigateToBannedScreen();
    } else if (status == 'pending') {
      // تم وضع في الانتظار
      updateClientStatus(data);
      navigateToPendingScreen();
    }
  }
}
```

### 4. التحقق الدوري من الحالة:

```dart
void startStatusCheck() {
  Timer.periodic(Duration(minutes: 5), (timer) async {
    try {
      final status = await apiService.getStatus();
      
      if (status['is_banned']) {
        timer.cancel();
        navigateToBannedScreen();
      } else if (!status['is_active'] || status['is_expired']) {
        timer.cancel();
        navigateToInactiveScreen();
      }
    } catch (e) {
      // خطأ في الاتصال - تجاهل
    }
  });
}
```

---

## أكواد الأخطاء

| الكود | المعنى | الإجراء |
|------|--------|---------|
| 200 | OK | نجح الطلب |
| 201 | Created | تم الإنشاء بنجاح |
| 401 | Unauthorized | التوكن غير صحيح - أعد تسجيل الدخول |
| 403 | Forbidden | الحساب محظور/في الانتظار/غير مفعل |
| 404 | Not Found | العميل غير موجود - قم بالتسجيل |
| 422 | Validation Error | خطأ في البيانات - اعرض رسائل الخطأ |
| 500 | Server Error | خطأ في الخادم - حاول مرة أخرى |

---

## ملاحظات مهمة للمطور

1. **حفظ التوكن:** احفظ التوكن بشكل آمن (SecureStorage/Keychain)
2. **إدارة الحالة:** تحقق من الحالة عند فتح التطبيق وعند تسجيل الدخول
3. **Push Notifications:** استقبل الإشعارات وحدّث الحالة فوراً
4. **معالجة الأخطاء:** اعرض رسائل واضحة للمستخدم حسب نوع الخطأ
5. **التحقق الدوري:** تحقق من الحالة بشكل دوري (كل 5 دقائق مثلاً)
6. **Device Token:** أرسل `device_token` عند تسجيل الدخول والتسجيل لتلقي الإشعارات

---

## مثال على Flow كامل

```
1. المستخدم يفتح التطبيق
   ↓
2. تحقق من وجود token
   ↓
3. إذا لا يوجد token → شاشة تسجيل الدخول
   ↓
4. إذا يوجد token → استدعاء GET /api/clients/status
   ↓
5. تحقق من الحالة:
   - pending → شاشة "في الانتظار"
   - banned → شاشة "حساب محظور"
   - active + expired → شاشة "انتهت المدة"
   - active + valid → شاشة الرئيسية
   ↓
6. عند استقبال push notification:
   - type: status_change → تحديث الحالة
   - status: active → فتح التطبيق
   - status: banned → شاشة الحظر
```

---

## نهاية الدليل

هذا الدليل يحتوي على كل ما تحتاجه لدمج API في التطبيق. يمكنك إعطاء هذا الدليل لأي AI ليقوم بتطبيقه مباشرة.

