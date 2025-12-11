# وثائق API الشاملة

## نظرة عامة

هذا API مخصص لإدارة العملاء والمنتجات. العملاء يسجلون الدخول من تطبيق موبايل عبر Google OAuth من Firebase، ويمكنهم إدارة منتجاتهم الخاصة.

**Base URL:** `http://your-domain.com/api`

**Authentication:** Bearer Token (Laravel Sanctum)

---

## جدول المحتويات

- [وثائق API الشاملة](#وثائق-api-الشاملة)
  - [نظرة عامة](#نظرة-عامة)
  - [جدول المحتويات](#جدول-المحتويات)
  - [المصادقة (Authentication)](#المصادقة-authentication)
  - [API للعملاء](#api-للعملاء)
    - [التسجيل](#التسجيل)
    - [تسجيل الدخول](#تسجيل-الدخول)
    - [الملف الشخصي](#الملف-الشخصي)
    - [تحديث الملف الشخصي](#تحديث-الملف-الشخصي)
    - [حالة العميل](#حالة-العميل)
    - [تسجيل الخروج](#تسجيل-الخروج)
    - [تحديث التوكن](#تحديث-التوكن)
  - [API للمنتجات](#api-للمنتجات)
    - [جلب جميع المنتجات](#جلب-جميع-المنتجات)
    - [جلب منتج واحد](#جلب-منتج-واحد)
    - [إضافة منتج](#إضافة-منتج)
    - [تحديث منتج](#تحديث-منتج)
    - [حذف منتج](#حذف-منتج)
    - [المنتجات منخفضة الكمية](#المنتجات-منخفضة-الكمية)
  - [أكواد الأخطاء ورسائلها](#أكواد-الأخطاء-ورسائلها)
    - [200 OK](#200-ok)
    - [201 Created](#201-created)
    - [401 Unauthorized](#401-unauthorized)
    - [403 Forbidden](#403-forbidden)
    - [404 Not Found](#404-not-found)
    - [422 Validation Error](#422-validation-error)
    - [500 Internal Server Error](#500-internal-server-error)
  - [حالات العميل](#حالات-العميل)
    - [1. pending (في الانتظار)](#1-pending-في-الانتظار)
    - [2. active (مفعل)](#2-active-مفعل)
    - [3. banned (محظور)](#3-banned-محظور)
  - [أنواع وحدات المنتجات](#أنواع-وحدات-المنتجات)
    - [1. وزن (weight)](#1-وزن-weight)
    - [2. قطعة (piece)](#2-قطعة-piece)
    - [3. كارتون (carton)](#3-كارتون-carton)
  - [ملاحظات مهمة](#ملاحظات-مهمة)
    - [التوكن (Token)](#التوكن-token)
    - [Firebase UID](#firebase-uid)
    - [المزود (Provider)](#المزود-provider)
    - [Device Token](#device-token)
    - [SKU (كود المنتج)](#sku-كود-المنتج)
    - [الكميات](#الكميات)
    - [تنبيهات انخفاض الكمية](#تنبيهات-انخفاض-الكمية)
    - [الأسعار](#الأسعار)
    - [آخر تسجيل دخول](#آخر-تسجيل-دخول)
  - [الدعم](#الدعم)

---

## المصادقة (Authentication)

جميع الـ endpoints المحمية تتطلب إرسال token في header:

```
Authorization: Bearer {token}
Accept: application/json
```

عند تسجيل الدخول أو التسجيل، ستحصل على token يجب استخدامه في جميع الطلبات المحمية.

---

## API للعملاء

### التسجيل

**Endpoint:** `POST /api/clients/register`

**Headers:**
- `Content-Type: application/json`
- `Accept: application/json`

**Request Body:**
- `firebase_uid` (required, string, unique): معرف Firebase للعميل
- `name` (required, string, max:255): اسم العميل
- `email` (required, email, unique): البريد الإلكتروني
- `phone` (optional, string, max:20): رقم الهاتف
- `photo_url` (optional, url, max:500): رابط الصورة الشخصية
- `provider` (required, string, in:google,facebook,apple): مزود OAuth
- `provider_id` (optional, string): معرف المزود
- `device_token` (optional, string): توكن الجهاز للإشعارات

**Response Success (201):**
- `success`: true
- `message`: "تم تسجيل العميل بنجاح"
- `data.client`: بيانات العميل
- `data.token`: token المصادقة
- `data.token_type`: "Bearer"

**Response Error (422):**
- `success`: false
- `message`: "خطأ في التحقق من البيانات"
- `errors`: مصفوفة بأخطاء التحقق

**Response Error (500):**
- `success`: false
- `message`: "حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى."

**ملاحظات:**
- الحالة الافتراضية للعميل الجديد هي `pending`
- يتم إنشاء token تلقائياً عند التسجيل

---

### تسجيل الدخول

**Endpoint:** `POST /api/clients/login`

**Headers:**
- `Content-Type: application/json`
- `Accept: application/json`

**Request Body:**
- `firebase_uid` (required, string): معرف Firebase للعميل
- `device_token` (optional, string): توكن الجهاز للإشعارات

**Response Success (200):**
- `success`: true
- `message`: "تم تسجيل الدخول بنجاح"
- `data.client`: بيانات العميل
- `data.token`: token المصادقة الجديد
- `data.token_type`: "Bearer"

**Response Error (404):**
- `success`: false
- `message`: "العميل غير موجود. يرجى التسجيل أولاً."

**Response Error (403):**
- `success`: false
- `message`: رسالة حسب حالة العميل:
  - "حسابك محظور. يرجى التواصل مع الدعم." (إذا كان محظور)
  - "حسابك في قائمة الانتظار. يرجى انتظار التفعيل من الإدارة." (إذا كان في الانتظار)
  - "انتهت مدة اشتراكك. يرجى تجديد الاشتراك." (إذا كان الاشتراك منتهي)
  - "حسابك غير مفعل أو انتهت مدة التفعيل. يرجى التواصل مع الدعم." (إذا لم يكن مفعل)

**Response Error (422):**
- `success`: false
- `message`: "خطأ في التحقق من البيانات"
- `errors`: مصفوفة بأخطاء التحقق

**Response Error (500):**
- `success`: false
- `message`: "حدث خطأ أثناء تسجيل الدخول. يرجى المحاولة مرة أخرى."

**ملاحظات:**
- يتم حذف جميع التوكنات القديمة وإنشاء token جديد
- يتم تحديث `last_login_at` تلقائياً

---

### الملف الشخصي

**Endpoint:** `GET /api/clients/profile`

**Headers:**
- `Authorization: Bearer {token}`
- `Accept: application/json`

**Response Success (200):**
- `success`: true
- `data.client`: بيانات العميل الكاملة

**Response Error (401):**
- `success`: false
- `message`: "المستخدم غير موجود أو غير مصرح له."

**Response Error (500):**
- `success`: false
- `message`: "حدث خطأ أثناء جلب بيانات الملف الشخصي. يرجى المحاولة مرة أخرى."

---

### تحديث الملف الشخصي

**Endpoint:** `PUT /api/clients/profile`

**Headers:**
- `Authorization: Bearer {token}`
- `Content-Type: application/json`
- `Accept: application/json`

**Request Body (جميع الحقول اختيارية):**
- `name` (optional, string, max:255): اسم العميل
- `phone` (optional, string, max:20): رقم الهاتف
- `photo_url` (optional, url, max:500): رابط الصورة الشخصية
- `device_token` (optional, string): توكن الجهاز للإشعارات

**Response Success (200):**
- `success`: true
- `message`: "تم تحديث البيانات بنجاح"
- `data.client`: بيانات العميل المحدثة

**Response Error (401):**
- `success`: false
- `message`: "المستخدم غير موجود أو غير مصرح له."

**Response Error (422):**
- `success`: false
- `message`: "خطأ في التحقق من البيانات"
- `errors`: مصفوفة بأخطاء التحقق

**Response Error (500):**
- `success`: false
- `message`: "حدث خطأ أثناء تحديث البيانات. يرجى المحاولة مرة أخرى."

---

### حالة العميل

**Endpoint:** `GET /api/clients/status`

**Headers:**
- `Authorization: Bearer {token}`
- `Accept: application/json`

**Response Success (200):**
- `success`: true
- `data.status`: حالة العميل (pending, active, banned)
- `data.activation_expires_at`: تاريخ انتهاء التفعيل
- `data.is_expired`: هل انتهت مدة التفعيل
- `data.is_active`: هل الحساب مفعل
- `data.is_pending`: هل الحساب في الانتظار
- `data.is_banned`: هل الحساب محظور

**Response Error (401):**
- `success`: false
- `message`: "المستخدم غير موجود أو غير مصرح له."

**Response Error (500):**
- `success`: false
- `message`: "حدث خطأ أثناء جلب حالة الحساب. يرجى المحاولة مرة أخرى."

---

### تسجيل الخروج

**Endpoint:** `POST /api/clients/logout`

**Headers:**
- `Authorization: Bearer {token}`
- `Accept: application/json`

**Response Success (200):**
- `success`: true
- `message`: "تم تسجيل الخروج بنجاح"

**ملاحظات:**
- يتم حذف token الحالي فقط
- يمكن للعميل تسجيل الدخول مرة أخرى باستخدام نفس `firebase_uid`

---

### تحديث التوكن

**Endpoint:** `POST /api/clients/refresh-token`

**Headers:**
- `Authorization: Bearer {token}`
- `Accept: application/json`

**Response Success (200):**
- `success`: true
- `message`: "تم تحديث التوكن بنجاح"
- `data.token`: token الجديد
- `data.token_type`: "Bearer"

**ملاحظات:**
- يتم حذف جميع التوكنات القديمة وإنشاء token جديد
- مفيد عند تجديد الأمان أو عند تغيير الجهاز

---

## API للمنتجات

### جلب جميع المنتجات

**Endpoint:** `GET /api/clients/products`

**Headers:**
- `Authorization: Bearer {token}`
- `Accept: application/json`

**Response Success (200):**
- `success`: true
- `data.products`: مصفوفة بجميع منتجات العميل
- `data.count`: عدد المنتجات

**Response Error (401):**
- `success`: false
- `message`: "المستخدم غير موجود أو غير مصرح له."

**Response Error (500):**
- `success`: false
- `message`: "حدث خطأ أثناء جلب المنتجات. يرجى المحاولة مرة أخرى."

**ملاحظات:**
- المنتجات مرتبة حسب تاريخ الإنشاء (الأحدث أولاً)
- كل منتج يحتوي على جميع التفاصيل بما فيها نوع الوحدة والكميات

---

### جلب منتج واحد

**Endpoint:** `GET /api/clients/products/{id}`

**Headers:**
- `Authorization: Bearer {token}`
- `Accept: application/json`

**Parameters:**
- `id` (required): معرف المنتج

**Response Success (200):**
- `success`: true
- `data.product`: بيانات المنتج الكاملة

**Response Error (401):**
- `success`: false
- `message`: "المستخدم غير موجود أو غير مصرح له."

**Response Error (404):**
- `success`: false
- `message`: "المنتج غير موجود أو ليس لديك صلاحية للوصول إليه."

**Response Error (500):**
- `success`: false
- `message`: "حدث خطأ أثناء جلب المنتج. يرجى المحاولة مرة أخرى."

---

### إضافة منتج

**Endpoint:** `POST /api/clients/products`

**Headers:**
- `Authorization: Bearer {token}`
- `Content-Type: application/json`
- `Accept: application/json`

**Request Body:**

**الحقول الأساسية (مطلوبة دائماً):**
- `name` (required, string, max:255): اسم المنتج
- `sku` (required, string, unique per client): كود المنتج
- `purchase_price` (required, numeric, min:0): سعر الشراء
- `wholesale_price` (required, numeric, min:0): سعر البيع بالجملة
- `retail_price` (required, numeric, min:0): سعر البيع بالمفرد
- `unit_type` (required, in:weight,piece,carton): نوع الوحدة
- `total_quantity` (required, numeric, min:0): الكمية الكلية
- `remaining_quantity` (required, numeric, min:0): الكمية المتبقية
- `min_quantity` (required, numeric, min:0): الحد الأدنى للتنبيه

**الحقول الإضافية حسب نوع الوحدة:**

**إذا `unit_type = "weight"`:**
- `weight` (required, numeric, min:0): وزن المنتج
- `weight_unit` (required, in:kg,g): وحدة الوزن

**إذا `unit_type = "carton"`:**
- `pieces_per_carton` (required, integer, min:1): عدد القطع في الكارتون
- `piece_price_in_carton` (required, numeric, min:0): سعر القطعة داخل الكارتون

**إذا `unit_type = "piece"`:**
- لا حاجة لحقول إضافية

**Response Success (201):**
- `success`: true
- `message`: "تم إضافة المنتج بنجاح"
- `data.product`: بيانات المنتج المضافة

**Response Error (401):**
- `success`: false
- `message`: "المستخدم غير موجود أو غير مصرح له."

**Response Error (422):**
- `success`: false
- `message`: "خطأ في التحقق من البيانات"
- `errors`: مصفوفة بأخطاء التحقق لكل حقل:
  - `name.required`: "اسم المنتج مطلوب."
  - `sku.required`: "كود المنتج (SKU) مطلوب."
  - `sku.unique`: "كود المنتج مستخدم مسبقاً. يرجى استخدام كود آخر."
  - `purchase_price.required`: "سعر الشراء مطلوب."
  - `purchase_price.numeric`: "سعر الشراء يجب أن يكون رقماً."
  - `purchase_price.min`: "سعر الشراء يجب أن يكون أكبر من أو يساوي صفر."
  - `wholesale_price.*`: رسائل مشابهة لسعر الشراء
  - `retail_price.*`: رسائل مشابهة لسعر الشراء
  - `unit_type.required`: "نوع الوحدة مطلوب."
  - `unit_type.in`: "نوع الوحدة يجب أن يكون: وزن، قطعة، أو كارتون."
  - `weight.required_if`: "الوزن مطلوب عند اختيار نوع الوحدة 'وزن'."
  - `weight_unit.required_if`: "وحدة الوزن مطلوبة عند اختيار نوع الوحدة 'وزن'."
  - `weight_unit.in`: "وحدة الوزن يجب أن تكون: كيلو (kg) أو غرام (g)."
  - `pieces_per_carton.required_if`: "عدد القطع في الكارتون مطلوب عند اختيار نوع الوحدة 'كارتون'."
  - `piece_price_in_carton.required_if`: "سعر القطعة داخل الكارتون مطلوب عند اختيار نوع الوحدة 'كارتون'."
  - `total_quantity.required`: "الكمية الكلية مطلوبة."
  - `remaining_quantity.required`: "الكمية المتبقية مطلوبة."
  - `remaining_quantity`: "الكمية المتبقية لا يمكن أن تكون أكبر من الكمية الكلية."
  - `min_quantity.required`: "الحد الأدنى للتنبيه مطلوب."

**Response Error (500):**
- `success`: false
- `message`: "حدث خطأ أثناء إضافة المنتج. يرجى المحاولة مرة أخرى."

---

### تحديث منتج

**Endpoint:** `PUT /api/clients/products/{id}`

**Headers:**
- `Authorization: Bearer {token}`
- `Content-Type: application/json`
- `Accept: application/json`

**Parameters:**
- `id` (required): معرف المنتج

**Request Body:**

نفس الحقول المستخدمة في إضافة منتج. جميع الحقول مطلوبة.

**Response Success (200):**
- `success`: true
- `message`: "تم تحديث المنتج بنجاح"
- `data.product`: بيانات المنتج المحدثة

**Response Error (401):**
- `success`: false
- `message`: "المستخدم غير موجود أو غير مصرح له."

**Response Error (404):**
- `success`: false
- `message`: "المنتج غير موجود أو ليس لديك صلاحية للوصول إليه."

**Response Error (422):**
- `success`: false
- `message`: "خطأ في التحقق من البيانات"
- `errors`: مصفوفة بأخطاء التحقق (نفس رسائل إضافة منتج)

**Response Error (500):**
- `success`: false
- `message`: "حدث خطأ أثناء تحديث المنتج. يرجى المحاولة مرة أخرى."

---

### حذف منتج

**Endpoint:** `DELETE /api/clients/products/{id}`

**Headers:**
- `Authorization: Bearer {token}`
- `Accept: application/json`

**Parameters:**
- `id` (required): معرف المنتج

**Response Success (200):**
- `success`: true
- `message`: "تم حذف المنتج بنجاح"

**Response Error (401):**
- `success`: false
- `message`: "المستخدم غير موجود أو غير مصرح له."

**Response Error (404):**
- `success`: false
- `message`: "المنتج غير موجود أو ليس لديك صلاحية للوصول إليه."

**Response Error (500):**
- `success`: false
- `message`: "حدث خطأ أثناء حذف المنتج. يرجى المحاولة مرة أخرى."

---

### المنتجات منخفضة الكمية

**Endpoint:** `GET /api/clients/products/low-stock`

**Headers:**
- `Authorization: Bearer {token}`
- `Accept: application/json`

**Response Success (200):**
- `success`: true
- `data.products`: مصفوفة بالمنتجات منخفضة الكمية
- `data.count`: عدد المنتجات منخفضة الكمية

**Response Error (401):**
- `success`: false
- `message`: "المستخدم غير موجود أو غير مصرح له."

**Response Error (500):**
- `success`: false
- `message`: "حدث خطأ أثناء جلب المنتجات منخفضة الكمية. يرجى المحاولة مرة أخرى."

**ملاحظات:**
- المنتجات مرتبة حسب الكمية المتبقية (الأقل أولاً)
- المنتج يعتبر منخفض الكمية عندما يكون `remaining_quantity <= min_quantity`

---

## أكواد الأخطاء ورسائلها

### 200 OK
الطلب نجح.

### 201 Created
تم إنشاء المورد بنجاح (مثل تسجيل عميل جديد أو إضافة منتج).

### 401 Unauthorized
غير مصرح - التوكن غير صحيح أو منتهي أو غير موجود.

**الرسائل الشائعة:**
- "المستخدم غير موجود أو غير مصرح له."
- "Unauthenticated."

**الحل:**
- تأكد من إرسال token في header `Authorization: Bearer {token}`
- تأكد من صحة token
- قم بتسجيل الدخول مرة أخرى للحصول على token جديد

### 403 Forbidden
ممنوع - الحساب محظور أو في الانتظار أو غير مفعل.

**الرسائل الشائعة:**
- "حسابك محظور. يرجى التواصل مع الدعم."
- "حسابك في قائمة الانتظار. يرجى انتظار التفعيل من الإدارة."
- "انتهت مدة اشتراكك. يرجى تجديد الاشتراك."
- "حسابك غير مفعل أو انتهت مدة التفعيل. يرجى التواصل مع الدعم."

**الحل:**
- تواصل مع الإدارة لتفعيل الحساب
- انتظر التفعيل إذا كان الحساب في الانتظار
- قم بتجديد الاشتراك إذا كان منتهياً

### 404 Not Found
المورد غير موجود.

**الرسائل الشائعة:**
- "العميل غير موجود. يرجى التسجيل أولاً."
- "المنتج غير موجود أو ليس لديك صلاحية للوصول إليه."

**الحل:**
- تأكد من صحة معرف المورد
- تأكد من أن المورد موجود وأن لديك صلاحية للوصول إليه

### 422 Validation Error
خطأ في التحقق من البيانات المرسلة.

**الرسائل:**
- `message`: "خطأ في التحقق من البيانات"
- `errors`: مصفوفة بأخطاء التحقق لكل حقل

**الحل:**
- راجع رسائل الأخطاء في `errors`
- تأكد من إرسال جميع الحقول المطلوبة
- تأكد من صحة نوع البيانات (نص، رقم، إلخ)
- تأكد من القيود (min, max, unique, إلخ)

### 500 Internal Server Error
خطأ في الخادم.

**الرسائل الشائعة:**
- "حدث خطأ أثناء [العملية]. يرجى المحاولة مرة أخرى."

**الحل:**
- حاول مرة أخرى بعد قليل
- إذا استمر الخطأ، تواصل مع الدعم الفني

---

## حالات العميل

كل عميل يمر بأربع حالات رئيسية:

### 1. pending (في الانتظار)
- **الوصف:** هذه هي الحالة الافتراضية لأي عميل جديد يسجل في التطبيق.
- **السلوك:** لا يمكن للعميل تسجيل الدخول أو استخدام خدمات التطبيق في هذه الحالة.
- **الإجراء المطلوب:** يجب على الإدارة تفعيل الحساب يدوياً من لوحة التحكم.

### 2. active (مفعل)
- **الوصف:** العميل مفعل ويمكنه استخدام جميع ميزات التطبيق.
- **السلوك:** يمكن للعميل تسجيل الدخول واستخدام جميع الـ APIs.
- **مدة التفعيل:** يتم تحديد مدة التفعيل (بالأشهر) من قبل الإدارة عند التفعيل.
- **انتهاء التفعيل:** إذا انتهت مدة التفعيل (`activation_expires_at` أصبح في الماضي)، يمكن للإدارة تعيين الحالة إلى `expired`.

### 3. banned (محظور)
- **الوصف:** تم حظر حساب العميل من قبل الإدارة.
- **السلوك:** لا يمكن للعميل تسجيل الدخول أو استخدام خدمات التطبيق.
- **الإجراء المطلوب:** يمكن للإدارة إلغاء حظر الحساب يدوياً من لوحة التحكم.

### 4. expired (انتهى الاشتراك)
- **الوصف:** انتهت مدة اشتراك العميل ويحتاج إلى تجديد.
- **السلوك:** لا يمكن للعميل تسجيل الدخول أو استخدام خدمات التطبيق.
- **الإجراء المطلوب:** يجب على الإدارة تجديد الاشتراك (تفعيل الحساب مرة أخرى) من لوحة التحكم.
- **الفرق عن active منتهي:** هذه حالة منفصلة يمكن للإدارة تعيينها يدوياً للعملاء الذين انتهت مدة اشتراكهم.

**آلية التحقق من الحالة عند تسجيل الدخول:**

عند محاولة العميل تسجيل الدخول، يقوم الـ API بالتحقق من حالته:

1. إذا كانت الحالة `banned` → يتم رفض تسجيل الدخول برسالة: "حسابك محظور. يرجى التواصل مع الدعم."
2. إذا كانت الحالة `pending` → يتم رفض تسجيل الدخول برسالة: "حسابك في قائمة الانتظار. يرجى انتظار التفعيل من الإدارة."
3. إذا كانت الحالة `expired` → يتم رفض تسجيل الدخول برسالة: "انتهت مدة اشتراكك. يرجى تجديد الاشتراك."
4. إذا كانت الحالة `active` ولكن `activation_expires_at` قد انتهى → يتم رفض تسجيل الدخول برسالة: "حسابك غير مفعل أو انتهت مدة التفعيل. يرجى التواصل مع الدعم."
5. إذا كانت الحالة `active` ولم تنتهِ مدة التفعيل → يتم تسجيل الدخول بنجاح.

---

## أنواع وحدات المنتجات

### 1. وزن (weight)
يستخدم للمنتجات التي تباع بالوزن (كيلو أو غرام).

**الحقول المطلوبة:**
- `weight`: الوزن (رقم)
- `weight_unit`: وحدة الوزن (`kg` للكيلو أو `g` للغرام)

**مثال على المنتجات:**
- أرز، سكر، قهوة، بهارات، إلخ

### 2. قطعة (piece)
يستخدم للمنتجات التي تباع بالقطعة.

**لا يتطلب حقول إضافية**

**مثال على المنتجات:**
- هواتف، أجهزة إلكترونية، ملابس، إلخ

### 3. كارتون (carton)
يستخدم للمنتجات التي تباع بالكارتون.

**الحقول المطلوبة:**
- `pieces_per_carton`: عدد القطع في الكارتون (عدد صحيح)
- `piece_price_in_carton`: سعر القطعة داخل الكارتون (رقم)

**مثال على المنتجات:**
- مشروبات غازية، منتجات غذائية معبأة، إلخ

---

## ملاحظات مهمة

### التوكن (Token)
- يجب إرسال التوكن في header `Authorization` بصيغة `Bearer {token}` لجميع الطلبات المحمية.
- عند تسجيل الدخول، يتم حذف جميع التوكنات القديمة وإنشاء token جديد.
- يمكن استخدام `refresh-token` endpoint لتجديد التوكن.

### Firebase UID
- يجب أن يكون `firebase_uid` فريداً لكل عميل.
- يتم الحصول عليه من Firebase Authentication.
- لا يمكن تغييره بعد التسجيل.

### المزود (Provider)
- القيم المدعومة هي: `google`, `facebook`, `apple`.
- يتم تحديده عند التسجيل ولا يمكن تغييره.

### Device Token
- يُستخدم لإرسال الإشعارات للعميل.
- يمكن تحديثه في أي وقت عبر `updateProfile` أو `login`.
- اختياري.

### SKU (كود المنتج)
- يجب أن يكون فريداً لكل عميل (يمكن لعميلين مختلفين استخدام نفس SKU).
- لا يمكن تغييره بعد الإنشاء (يجب حذف المنتج وإعادة إنشائه).

### الكميات
- `total_quantity`: العدد الكلي للمنتج.
- `remaining_quantity`: العدد المتبقي (يجب أن يكون ≤ total_quantity).
- `min_quantity`: الحد الأدنى للتنبيه.
- عند إضافة منتج جديد، يمكنك تعيين `remaining_quantity = total_quantity`.

### تنبيهات انخفاض الكمية
- يتم تحديث `is_low_stock` تلقائياً عند الحفظ.
- عندما يكون `remaining_quantity <= min_quantity`، يصبح المنتج منخفض الكمية.
- يمكن استخدام endpoint `/api/clients/products/low-stock` للحصول على جميع المنتجات منخفضة الكمية.

### الأسعار
- جميع الأسعار بالأرقام (decimal).
- يجب أن تكون أكبر من أو تساوي صفر.
- استخدم `step="0.01"` في حقول الإدخال لدعم الكسور.

### آخر تسجيل دخول
- يتم تحديث `last_login_at` تلقائياً عند تسجيل الدخول.

---

## الدعم

للمساعدة والدعم، يرجى التواصل مع فريق التطوير.

