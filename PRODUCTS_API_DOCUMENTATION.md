# وثائق API للمنتجات

## نظرة عامة

هذا API مخصص لإدارة منتجات العملاء. كل عميل يمكنه إدارة منتجاته الخاصة مع تتبع المخزون والأسعار والوحدات المختلفة.

**Base URL:** `http://your-domain.com/api`

**Authentication:** Bearer Token (Laravel Sanctum)

---

## جدول المحتويات

1. [جلب المنتجات](#جلب-المنتجات)
2. [جلب منتج واحد](#جلب-منتج-واحد)
3. [إضافة منتج](#إضافة-منتج)
4. [تحديث منتج](#تحديث-منتج)
5. [حذف منتج](#حذف-منتج)
6. [المنتجات منخفضة الكمية](#المنتجات-منخفضة-الكمية)
7. [أنواع الوحدات](#أنواع-الوحدات)
8. [أكواد الأخطاء](#أكواد-الأخطاء)

---

## جلب المنتجات

الحصول على قائمة بجميع منتجات العميل الحالي.

### Endpoint

```
GET /api/clients/products
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
    "products": [
      {
        "id": 1,
        "client_id": 1,
        "name": "منتج مثال",
        "sku": "PROD001",
        "purchase_price": "100.00",
        "wholesale_price": "150.00",
        "retail_price": "200.00",
        "unit_type": "piece",
        "weight": null,
        "weight_unit": null,
        "pieces_per_carton": null,
        "piece_price_in_carton": null,
        "total_quantity": "100.00",
        "remaining_quantity": "75.00",
        "min_quantity": "10.00",
        "is_low_stock": false,
        "created_at": "2025-12-10T18:00:00.000000Z",
        "updated_at": "2025-12-10T18:00:00.000000Z"
      }
    ]
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

## جلب منتج واحد

الحصول على تفاصيل منتج محدد.

### Endpoint

```
GET /api/clients/products/{id}
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
    "product": {
      "id": 1,
      "client_id": 1,
      "name": "منتج مثال",
      "sku": "PROD001",
      "purchase_price": "100.00",
      "wholesale_price": "150.00",
      "retail_price": "200.00",
      "unit_type": "piece",
      "total_quantity": "100.00",
      "remaining_quantity": "75.00",
      "min_quantity": "10.00",
      "is_low_stock": false,
      "created_at": "2025-12-10T18:00:00.000000Z",
      "updated_at": "2025-12-10T18:00:00.000000Z"
    }
  }
}
```

### Response (Error - 404)

```json
{
  "success": false,
  "message": "Product not found."
}
```

---

## إضافة منتج

إضافة منتج جديد للعميل الحالي.

### Endpoint

```
POST /api/clients/products
```

### Headers

```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### Request Body

**الحقول الأساسية (مطلوبة دائماً):**
```json
{
  "name": "string (required, max:255)",
  "sku": "string (required, unique per client)",
  "purchase_price": "number (required, min:0)",
  "wholesale_price": "number (required, min:0)",
  "retail_price": "number (required, min:0)",
  "unit_type": "weight|piece|carton (required)",
  "total_quantity": "number (required, min:0)",
  "remaining_quantity": "number (required, min:0, max:total_quantity)",
  "min_quantity": "number (required, min:0)"
}
```

**الحقول الإضافية حسب نوع الوحدة:**

**إذا `unit_type = "weight"`:**
```json
{
  "weight": "number (required, min:0)",
  "weight_unit": "kg|g (required)"
}
```

**إذا `unit_type = "carton"`:**
```json
{
  "pieces_per_carton": "integer (required, min:1)",
  "piece_price_in_carton": "number (required, min:0)"
}
```

**إذا `unit_type = "piece"`:**
لا حاجة لحقول إضافية

### مثال على الطلب - منتج بالوزن

```json
{
  "name": "أرز بسمتي",
  "sku": "RICE001",
  "purchase_price": 50.00,
  "wholesale_price": 60.00,
  "retail_price": 70.00,
  "unit_type": "weight",
  "weight": 5,
  "weight_unit": "kg",
  "total_quantity": 100,
  "remaining_quantity": 100,
  "min_quantity": 10
}
```

### مثال على الطلب - منتج بالقطعة

```json
{
  "name": "هاتف محمول",
  "sku": "PHONE001",
  "purchase_price": 500.00,
  "wholesale_price": 600.00,
  "retail_price": 700.00,
  "unit_type": "piece",
  "total_quantity": 50,
  "remaining_quantity": 50,
  "min_quantity": 5
}
```

### مثال على الطلب - منتج بالكارتون

```json
{
  "name": "مشروبات غازية",
  "sku": "DRINK001",
  "purchase_price": 20.00,
  "wholesale_price": 25.00,
  "retail_price": 30.00,
  "unit_type": "carton",
  "pieces_per_carton": 24,
  "piece_price_in_carton": 1.25,
  "total_quantity": 100,
  "remaining_quantity": 100,
  "min_quantity": 10
}
```

### Response (Success - 201)

```json
{
  "success": true,
  "message": "تم إضافة المنتج بنجاح",
  "data": {
    "product": {
      "id": 1,
      "client_id": 1,
      "name": "أرز بسمتي",
      "sku": "RICE001",
      "purchase_price": "50.00",
      "wholesale_price": "60.00",
      "retail_price": "70.00",
      "unit_type": "weight",
      "weight": "5.000",
      "weight_unit": "kg",
      "total_quantity": "100.00",
      "remaining_quantity": "100.00",
      "min_quantity": "10.00",
      "is_low_stock": false,
      "created_at": "2025-12-10T18:00:00.000000Z",
      "updated_at": "2025-12-10T18:00:00.000000Z"
    }
  }
}
```

### Response (Error - 422)

```json
{
  "success": false,
  "message": "خطأ في التحقق من البيانات",
  "errors": {
    "sku": ["كود المنتج مستخدم مسبقاً"],
    "weight": ["الوزن مطلوب عند اختيار نوع الوحدة وزن"]
  }
}
```

---

## تحديث منتج

تحديث بيانات منتج موجود.

### Endpoint

```
PUT /api/clients/products/{id}
```

### Headers

```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### Request Body

نفس الحقول المستخدمة في إضافة منتج. جميع الحقول مطلوبة.

### Response (Success - 200)

```json
{
  "success": true,
  "message": "تم تحديث المنتج بنجاح",
  "data": {
    "product": {
      "id": 1,
      "name": "أرز بسمتي محدث",
      "sku": "RICE001",
      "remaining_quantity": "50.00",
      "is_low_stock": false,
      ...
    }
  }
}
```

### Response (Error - 404)

```json
{
  "success": false,
  "message": "Product not found."
}
```

---

## حذف منتج

حذف منتج من قائمة منتجات العميل.

### Endpoint

```
DELETE /api/clients/products/{id}
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
  "message": "تم حذف المنتج بنجاح"
}
```

### Response (Error - 404)

```json
{
  "success": false,
  "message": "Product not found."
}
```

---

## المنتجات منخفضة الكمية

الحصول على قائمة المنتجات التي انخفضت كميتها عن الحد الأدنى.

### Endpoint

```
GET /api/clients/products/low-stock
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
    "products": [
      {
        "id": 1,
        "name": "منتج مثال",
        "sku": "PROD001",
        "remaining_quantity": "5.00",
        "min_quantity": "10.00",
        "is_low_stock": true,
        ...
      }
    ],
    "count": 1
  }
}
```

---

## أنواع الوحدات

### 1. وزن (weight)

يستخدم للمنتجات التي تباع بالوزن (كيلو أو غرام).

**الحقول المطلوبة:**
- `weight` - الوزن (رقم)
- `weight_unit` - وحدة الوزن: `kg` (كيلو) أو `g` (غرام)

**مثال:**
```json
{
  "unit_type": "weight",
  "weight": 5,
  "weight_unit": "kg"
}
```

### 2. قطعة (piece)

يستخدم للمنتجات التي تباع بالقطعة.

**لا يتطلب حقول إضافية**

**مثال:**
```json
{
  "unit_type": "piece"
}
```

### 3. كارتون (carton)

يستخدم للمنتجات التي تباع بالكارتون.

**الحقول المطلوبة:**
- `pieces_per_carton` - عدد القطع في الكارتون (عدد صحيح)
- `piece_price_in_carton` - سعر القطعة داخل الكارتون (رقم)

**مثال:**
```json
{
  "unit_type": "carton",
  "pieces_per_carton": 24,
  "piece_price_in_carton": 1.25
}
```

---

## إدارة المخزون

### الكميات

- **total_quantity**: العدد الكلي للمنتج
- **remaining_quantity**: العدد المتبقي (يجب أن يكون ≤ total_quantity)
- **min_quantity**: الحد الأدنى للتنبيه

### تنبيهات انخفاض الكمية

- عندما يكون `remaining_quantity <= min_quantity`، يتم تعيين `is_low_stock = true` تلقائياً
- يمكن استخدام endpoint `/api/clients/products/low-stock` للحصول على جميع المنتجات منخفضة الكمية

---

## أمثلة على الاستخدام

### JavaScript (Fetch API)

#### جلب المنتجات

```javascript
const getProducts = async () => {
  const token = localStorage.getItem('token');
  const response = await fetch('http://your-domain.com/api/clients/products', {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });

  const data = await response.json();
  if (data.success) {
    return data.data.products;
  } else {
    throw new Error(data.message);
  }
};
```

#### إضافة منتج

```javascript
const addProduct = async (productData) => {
  const token = localStorage.getItem('token');
  const response = await fetch('http://your-domain.com/api/clients/products', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify(productData)
  });

  const data = await response.json();
  if (data.success) {
    return data.data.product;
  } else {
    throw new Error(data.message);
  }
};

// مثال: إضافة منتج بالوزن
await addProduct({
  name: 'أرز بسمتي',
  sku: 'RICE001',
  purchase_price: 50.00,
  wholesale_price: 60.00,
  retail_price: 70.00,
  unit_type: 'weight',
  weight: 5,
  weight_unit: 'kg',
  total_quantity: 100,
  remaining_quantity: 100,
  min_quantity: 10
});
```

#### تحديث منتج

```javascript
const updateProduct = async (productId, productData) => {
  const token = localStorage.getItem('token');
  const response = await fetch(`http://your-domain.com/api/clients/products/${productId}`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify(productData)
  });

  const data = await response.json();
  if (data.success) {
    return data.data.product;
  } else {
    throw new Error(data.message);
  }
};
```

#### حذف منتج

```javascript
const deleteProduct = async (productId) => {
  const token = localStorage.getItem('token');
  const response = await fetch(`http://your-domain.com/api/clients/products/${productId}`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });

  const data = await response.json();
  if (data.success) {
    return true;
  } else {
    throw new Error(data.message);
  }
};
```

#### جلب المنتجات منخفضة الكمية

```javascript
const getLowStockProducts = async () => {
  const token = localStorage.getItem('token');
  const response = await fetch('http://your-domain.com/api/clients/products/low-stock', {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });

  const data = await response.json();
  if (data.success) {
    return data.data.products;
  } else {
    throw new Error(data.message);
  }
};
```

### cURL

#### جلب المنتجات

```bash
curl -X GET http://your-domain.com/api/clients/products \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

#### إضافة منتج

```bash
curl -X POST http://your-domain.com/api/clients/products \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "أرز بسمتي",
    "sku": "RICE001",
    "purchase_price": 50.00,
    "wholesale_price": 60.00,
    "retail_price": 70.00,
    "unit_type": "weight",
    "weight": 5,
    "weight_unit": "kg",
    "total_quantity": 100,
    "remaining_quantity": 100,
    "min_quantity": 10
  }'
```

#### تحديث منتج

```bash
curl -X PUT http://your-domain.com/api/clients/products/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "أرز بسمتي محدث",
    "sku": "RICE001",
    "purchase_price": 55.00,
    "wholesale_price": 65.00,
    "retail_price": 75.00,
    "unit_type": "weight",
    "weight": 5,
    "weight_unit": "kg",
    "total_quantity": 100,
    "remaining_quantity": 50,
    "min_quantity": 10
  }'
```

#### حذف منتج

```bash
curl -X DELETE http://your-domain.com/api/clients/products/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

#### جلب المنتجات منخفضة الكمية

```bash
curl -X GET http://your-domain.com/api/clients/products/low-stock \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

---

## أكواد الأخطاء

| الكود | المعنى | الوصف |
|------|--------|-------|
| 200 | OK | الطلب نجح |
| 201 | Created | تم إنشاء المنتج بنجاح |
| 401 | Unauthorized | غير مصرح - التوكن غير صحيح أو منتهي |
| 404 | Not Found | المنتج غير موجود |
| 422 | Validation Error | خطأ في التحقق من البيانات |
| 500 | Internal Server Error | خطأ في الخادم |

---

## ملاحظات مهمة

1. **SKU فريد لكل عميل:** كل عميل يمكنه استخدام نفس SKU، لكن SKU يجب أن يكون فريداً داخل منتجات نفس العميل.

2. **الكمية المتبقية:** يجب أن تكون `remaining_quantity <= total_quantity`. عند إضافة منتج جديد، يمكنك تعيين `remaining_quantity = total_quantity`.

3. **تنبيهات انخفاض الكمية:** يتم تحديث `is_low_stock` تلقائياً عند الحفظ. عندما يكون `remaining_quantity <= min_quantity`، يصبح المنتج منخفض الكمية.

4. **أنواع الوحدات:** يجب إرسال الحقول الإضافية حسب نوع الوحدة:
   - `weight`: يتطلب `weight` و `weight_unit`
   - `piece`: لا يتطلب حقول إضافية
   - `carton`: يتطلب `pieces_per_carton` و `piece_price_in_carton`

5. **الأسعار:** جميع الأسعار بالأرقام (decimal). استخدم `step="0.01"` في حقول الإدخال.

6. **الكميات:** جميع الكميات بالأرقام (decimal) لدعم الكسور إذا لزم الأمر.

---

## أمثلة على أنواع المنتجات

### منتج بالوزن (كيلو)

```json
{
  "name": "سكر أبيض",
  "sku": "SUGAR001",
  "purchase_price": 3.50,
  "wholesale_price": 4.00,
  "retail_price": 4.50,
  "unit_type": "weight",
  "weight": 1,
  "weight_unit": "kg",
  "total_quantity": 500,
  "remaining_quantity": 500,
  "min_quantity": 50
}
```

### منتج بالوزن (غرام)

```json
{
  "name": "قهوة محمصة",
  "sku": "COFFEE001",
  "purchase_price": 25.00,
  "wholesale_price": 30.00,
  "retail_price": 35.00,
  "unit_type": "weight",
  "weight": 250,
  "weight_unit": "g",
  "total_quantity": 1000,
  "remaining_quantity": 1000,
  "min_quantity": 100
}
```

### منتج بالقطعة

```json
{
  "name": "هاتف ذكي",
  "sku": "PHONE001",
  "purchase_price": 500.00,
  "wholesale_price": 600.00,
  "retail_price": 700.00,
  "unit_type": "piece",
  "total_quantity": 50,
  "remaining_quantity": 50,
  "min_quantity": 5
}
```

### منتج بالكارتون

```json
{
  "name": "مشروبات غازية",
  "sku": "DRINK001",
  "purchase_price": 20.00,
  "wholesale_price": 25.00,
  "retail_price": 30.00,
  "unit_type": "carton",
  "pieces_per_carton": 24,
  "piece_price_in_carton": 1.25,
  "total_quantity": 100,
  "remaining_quantity": 100,
  "min_quantity": 10
}
```

---

## الدعم

للمساعدة والدعم، يرجى التواصل مع فريق التطوير.

