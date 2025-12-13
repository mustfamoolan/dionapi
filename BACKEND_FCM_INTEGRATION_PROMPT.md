# Prompt للـ Backend AI - إضافة FCM Notifications

---

# 🎯 المطلوب

أنا أملك Backend API موجود بالفعل على `salesflowi.com/api`، وأريد إضافة نظام إرسال إشعارات Firebase Cloud Messaging (FCM) للتطبيق.

## Backend الحالي

- **Base URL**: `https://salesflowi.com/api`
- **Database**: MySQL/PostgreSQL + Firebase Firestore
- **Language**: (حدد اللغة - PHP/Node.js/Python/etc.)

### Endpoints الموجودة حالياً:

```
POST /clients/register
POST /clients/login
GET  /clients/profile
GET  /clients/status
PUT  /clients/{id}/update-status  ← هنا نحتاج إرسال إشعار!
```

---

## 📱 Firebase Firestore Structure

### Collection: `clients`
Document ID: `firebase_uid`

```json
{
  "id": 123,
  "firebase_uid": "abc123xyz...",
  "name": "أحمد محمد",
  "email": "ahmad@example.com",
  "phone": "07XXXXXXXXX",
  "status": "active",
  "activation_expires_at": "2025-12-31T23:59:59.000Z",
  "is_active": true,
  
  "fcm_token": "current_device_fcm_token_here",
  "fcm_tokens": ["token1", "token2", "token3"],
  "fcm_token_updated_at": "2024-12-13T12:00:00.000Z",
  "device_platform": "android"
}
```

**ملاحظة**: 
- `fcm_token` = آخر token للجهاز الحالي
- `fcm_tokens` = array لدعم أجهزة متعددة لنفس المستخدم

---

## 🎯 المطلوب بالضبط

### 1. إضافة Firebase Admin SDK

تثبيت الحزمة:
```bash
npm install firebase-admin
# أو
composer require kreait/firebase-php
# أو
pip install firebase-admin
```

تهيئة Firebase Admin:
```javascript
const admin = require('firebase-admin');
const serviceAccount = require('./path/to/serviceAccountKey.json');

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount)
});
```

---

### 2. إنشاء Helper Function لإرسال الإشعارات

```javascript
/**
 * إرسال إشعار FCM لمستخدم واحد
 * 
 * @param {string} firebaseUid - Firebase UID للمستخدم
 * @param {object} notification - {title, body}
 * @param {object} data - {type, ...customData}
 * @returns {Promise<string>} messageId
 */
async function sendFCMNotification(firebaseUid, notification, data) {
  try {
    // 1. جلب FCM Token من Firestore
    const userDoc = await admin.firestore()
      .collection('clients')
      .doc(firebaseUid)
      .get();
    
    if (!userDoc.exists) {
      throw new Error('User not found');
    }
    
    const fcmToken = userDoc.data().fcm_token;
    
    if (!fcmToken) {
      console.log(`⚠️ No FCM token for user: ${firebaseUid}`);
      return null;
    }
    
    // 2. إرسال الإشعار
    const message = {
      token: fcmToken,
      notification: {
        title: notification.title,
        body: notification.body
      },
      data: data,
      android: {
        priority: 'high',
        notification: {
          sound: 'default',
          channelId: data.channel_id || 'account_status'
        }
      }
    };
    
    const response = await admin.messaging().send(message);
    console.log('✅ تم إرسال الإشعار:', response);
    return response;
    
  } catch (error) {
    console.error('❌ خطأ في إرسال الإشعار:', error);
    throw error;
  }
}

/**
 * إرسال إشعار لعدة مستخدمين
 */
async function sendFCMToMultiple(firebaseUids, notification, data) {
  const tokens = [];
  
  // جلب جميع الـ tokens
  for (const uid of firebaseUids) {
    const userDoc = await admin.firestore()
      .collection('clients')
      .doc(uid)
      .get();
    
    if (userDoc.exists && userDoc.data().fcm_token) {
      tokens.push(userDoc.data().fcm_token);
    }
  }
  
  if (tokens.length === 0) {
    return { successCount: 0, failureCount: 0 };
  }
  
  // إرسال multicast
  const message = {
    tokens: tokens,
    notification: notification,
    data: data,
    android: {
      priority: 'high'
    }
  };
  
  const response = await admin.messaging().sendMulticast(message);
  console.log(`✅ تم إرسال ${response.successCount} إشعار`);
  return response;
}

/**
 * إرسال لجميع المستخدمين النشطين
 */
async function sendFCMToAll(notification, data, filter = {}) {
  let query = admin.firestore().collection('clients');
  
  // تطبيق الفلاتر
  if (filter.status) {
    query = query.where('status', '==', filter.status);
  }
  
  const snapshot = await query.get();
  const tokens = [];
  
  snapshot.forEach(doc => {
    const fcmToken = doc.data().fcm_token;
    if (fcmToken) {
      tokens.push(fcmToken);
    }
  });
  
  // إرسال على دفعات (FCM يدعم 500 token/request)
  const batchSize = 500;
  let successCount = 0;
  let failureCount = 0;
  
  for (let i = 0; i < tokens.length; i += batchSize) {
    const batch = tokens.slice(i, i + batchSize);
    
    const response = await admin.messaging().sendMulticast({
      tokens: batch,
      notification: notification,
      data: data
    });
    
    successCount += response.successCount;
    failureCount += response.failureCount;
  }
  
  return { successCount, failureCount };
}
```

---

### 3. دمج في Endpoints الموجودة

#### عند تحديث حالة الحساب

```javascript
// PUT /clients/{id}/update-status
app.put('/clients/:id/update-status', async (req, res) => {
  const clientId = req.params.id;
  const { status } = req.body; // 'active', 'banned', 'expired', 'pending'
  
  try {
    // 1. جلب بيانات المستخدم
    const client = await db.query('SELECT * FROM clients WHERE id = ?', [clientId]);
    const firebaseUid = client.firebase_uid;
    
    // 2. تحديث في قاعدة البيانات
    await db.query('UPDATE clients SET status = ? WHERE id = ?', [status, clientId]);
    
    // 3. تحديث في Firestore
    await admin.firestore()
      .collection('clients')
      .doc(firebaseUid)
      .update({
        status: status,
        updated_at: admin.firestore.FieldValue.serverTimestamp()
      });
    
    // 4. إرسال إشعار FCM حسب النوع ⚡
    let notificationData = {};
    
    switch (status) {
      case 'active':
        notificationData = {
          notification: {
            title: 'تم تفعيل اشتراكك ✅',
            body: 'مبروك! تم تفعيل اشتراكك بنجاح'
          },
          data: {
            type: 'subscription_activated',
            status: 'active'
          }
        };
        break;
        
      case 'banned':
        notificationData = {
          notification: {
            title: 'حسابك محظور 🚫',
            body: 'تم حظر حسابك، يرجى التواصل مع الدعم'
          },
          data: {
            type: 'account_banned',
            status: 'banned',
            support_phone: '07737777424'
          }
        };
        break;
        
      case 'expired':
        notificationData = {
          notification: {
            title: 'انتهى اشتراكك ❌',
            body: 'اشتراكك انتهى، يرجى التجديد'
          },
          data: {
            type: 'subscription_expired',
            status: 'expired'
          }
        };
        break;
        
      case 'pending':
        notificationData = {
          notification: {
            title: 'حسابك قيد المراجعة ⏳',
            body: 'سيتم تفعيله قريباً'
          },
          data: {
            type: 'account_pending',
            status: 'pending'
          }
        };
        break;
    }
    
    // إرسال الإشعار
    await sendFCMNotification(
      firebaseUid,
      notificationData.notification,
      notificationData.data
    );
    
    // 5. إرجاع الاستجابة
    res.json({
      success: true,
      message: 'تم تحديث الحالة وإرسال الإشعار'
    });
    
  } catch (error) {
    console.error('❌ خطأ:', error);
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
});
```

---

### 4. إشعارات إضافية مفيدة

#### إشعار قبل انتهاء الاشتراك (Cron Job)

```javascript
// يعمل يومياً الساعة 9 صباحاً
cron.schedule('0 9 * * *', async () => {
  console.log('🔍 فحص الاشتراكات التي ستنتهي قريباً...');
  
  // تاريخ بعد 5 أيام
  const fiveDaysLater = new Date();
  fiveDaysLater.setDate(fiveDaysLater.getDate() + 5);
  
  // جلب المستخدمين الذين ينتهي اشتراكهم خلال 5 أيام
  const expiringClients = await db.query(`
    SELECT * FROM clients 
    WHERE activation_expires_at <= ? 
    AND activation_expires_at > NOW()
    AND status = 'active'
  `, [fiveDaysLater]);
  
  // إرسال إشعار لكل واحد
  for (const client of expiringClients) {
    const daysLeft = Math.ceil(
      (new Date(client.activation_expires_at) - new Date()) / (1000 * 60 * 60 * 24)
    );
    
    await sendFCMNotification(client.firebase_uid, {
      title: 'اشتراكك ينتهي قريباً ⏰',
      body: `باقي ${daysLeft} أيام على انتهاء اشتراكك`
    }, {
      type: 'subscription_expiring_soon',
      days_left: daysLeft.toString(),
      expires_at: client.activation_expires_at
    });
  }
});
```

---

## 📋 جميع أنواع الإشعارات (8 أنواع)

### 1. الديون

#### `overdue_debt` - دين متأخر
```javascript
await sendFCMNotification(firebaseUid, {
  title: 'دين متأخر ⚠️',
  body: 'لديك دين متأخر يحتاج متابعة'
}, {
  type: 'overdue_debt',
  debt_id: '123',
  customer_name: 'علي أحمد',
  amount: '100000',
  days_overdue: '10'
});
```

#### `debt_due_soon` - موعد سداد قريب
```javascript
await sendFCMNotification(firebaseUid, {
  title: 'موعد سداد قريب 📅',
  body: 'باقي 2 يوم على موعد السداد'
}, {
  type: 'debt_due_soon',
  debt_id: '456',
  days_left: '2',
  amount: '50000'
});
```

### 2. المخزون

#### `low_stock` - مخزون منخفض
```javascript
await sendFCMNotification(firebaseUid, {
  title: 'مخزون منخفض 📦',
  body: 'منتج قمح: الكمية الحالية 5 والحد الأدنى 10'
}, {
  type: 'low_stock',
  product_id: 'prod_123',
  product_name: 'قمح',
  current_quantity: '5',
  min_quantity: '10'
});
```

### 3. حالة الحساب/الاشتراك

#### `subscription_activated` - تفعيل اشتراك
```javascript
await sendFCMNotification(firebaseUid, {
  title: 'تم تفعيل اشتراكك ✅',
  body: 'مبروك! تم تفعيل اشتراكك بنجاح'
}, {
  type: 'subscription_activated',
  expires_at: '2025-12-31'
});
```

#### `subscription_expired` - انتهاء اشتراك
```javascript
await sendFCMNotification(firebaseUid, {
  title: 'انتهى اشتراكك ❌',
  body: 'اشتراكك انتهى، يرجى التجديد'
}, {
  type: 'subscription_expired',
  expired_at: '2024-12-13'
});
```

#### `subscription_expiring_soon` - ينتهي قريباً
```javascript
await sendFCMNotification(firebaseUid, {
  title: 'اشتراكك ينتهي قريباً ⏰',
  body: 'باقي 5 أيام على انتهاء اشتراكك'
}, {
  type: 'subscription_expiring_soon',
  days_left: '5',
  expires_at: '2024-12-18'
});
```

#### `account_banned` - حساب محظور
```javascript
await sendFCMNotification(firebaseUid, {
  title: 'حسابك محظور 🚫',
  body: 'تم حظر حسابك، يرجى التواصل مع الدعم'
}, {
  type: 'account_banned',
  reason: 'مخالفة شروط الاستخدام',
  support_phone: '07737777424'
});
```

#### `account_pending` - قيد المراجعة
```javascript
await sendFCMNotification(firebaseUid, {
  title: 'حسابك قيد المراجعة ⏳',
  body: 'سيتم تفعيله خلال 24 ساعة'
}, {
  type: 'account_pending',
  submitted_at: '2024-12-13'
});
```

---

## 🔧 Endpoints الجديدة المطلوبة

### 1. إرسال إشعار لمستخدم واحد

```
POST /api/notifications/send-to-user

Request:
{
  "user_id": "firebase_uid",
  "notification": {
    "title": "عنوان الإشعار",
    "body": "نص الإشعار"
  },
  "data": {
    "type": "overdue_debt",
    "debt_id": "123",
    "message": "رسالة إضافية"
  }
}

Response:
{
  "success": true,
  "message": "تم إرسال الإشعار بنجاح",
  "message_id": "fcm_message_id_here"
}
```

### 2. إرسال إشعار لعدة مستخدمين

```
POST /api/notifications/send-to-multiple

Request:
{
  "user_ids": ["uid1", "uid2", "uid3"],
  "notification": {
    "title": "عنوان الإشعار",
    "body": "نص الإشعار"
  },
  "data": {
    "type": "low_stock"
  }
}

Response:
{
  "success": true,
  "message": "تم إرسال الإشعارات",
  "sent_count": 150,
  "failed_count": 2
}
```

### 3. إرسال إشعار لجميع المستخدمين

```
POST /api/notifications/send-to-all

Request:
{
  "notification": {
    "title": "تحديث مهم",
    "body": "تم إضافة ميزات جديدة"
  },
  "data": {
    "type": "general"
  },
  "filter": {
    "status": "active"  // اختياري
  }
}

Response:
{
  "success": true,
  "message": "تم إرسال الإشعارات لجميع المستخدمين",
  "sent_count": 500,
  "failed_count": 10
}
```

---

## 🤖 Scheduled Jobs (Cron) - اختياري لكن مفيد جداً

### 1. فحص الديون المتأخرة (كل ساعة)

```javascript
// npm install node-cron

const cron = require('node-cron');

// كل ساعة
cron.schedule('0 * * * *', async () => {
  console.log('🔍 فحص الديون المتأخرة...');
  
  // 1. جلب الديون المتأخرة من Firestore
  const overdueDebts = await admin.firestore()
    .collection('debts')
    .where('isFullyPaid', '==', false)
    .where('dueDate', '<', new Date())
    .get();
  
  // 2. تجميع حسب client_uid
  const debtsByClient = {};
  overdueDebts.forEach(doc => {
    const debt = doc.data();
    const clientUid = debt.clientUid;
    
    if (!debtsByClient[clientUid]) {
      debtsByClient[clientUid] = [];
    }
    debtsByClient[clientUid].push({
      id: doc.id,
      ...debt
    });
  });
  
  // 3. إرسال إشعار لكل عميل لديه ديون متأخرة
  for (const [clientUid, debts] of Object.entries(debtsByClient)) {
    const totalOverdue = debts.reduce((sum, d) => sum + d.remainingAmount, 0);
    
    await sendFCMNotification(clientUid, {
      title: 'ديون متأخرة ⚠️',
      body: `لديك ${debts.length} دين متأخر بقيمة ${totalOverdue} IQD`
    }, {
      type: 'overdue_debt',
      count: debts.length.toString(),
      total_amount: totalOverdue.toString()
    });
  }
  
  console.log(`✅ تم إرسال ${Object.keys(debtsByClient).length} إشعار`);
});
```

### 2. تذكير بمواعيد السداد (كل يوم الساعة 9 صباحاً)

```javascript
// كل يوم الساعة 9 صباحاً
cron.schedule('0 9 * * *', async () => {
  console.log('🔍 فحص مواعيد السداد القريبة...');
  
  const twoDaysLater = new Date();
  twoDaysLater.setDate(twoDaysLater.getDate() + 2);
  
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  
  // الديون التي موعد سدادها خلال يومين
  const dueSoon = await admin.firestore()
    .collection('debts')
    .where('isFullyPaid', '==', false)
    .where('dueDate', '>=', tomorrow)
    .where('dueDate', '<=', twoDaysLater)
    .get();
  
  // إرسال تذكير لكل دين
  dueSoon.forEach(async (doc) => {
    const debt = doc.data();
    const daysLeft = Math.ceil(
      (debt.dueDate.toDate() - new Date()) / (1000 * 60 * 60 * 24)
    );
    
    await sendFCMNotification(debt.clientUid, {
      title: 'موعد سداد قريب 📅',
      body: `باقي ${daysLeft} يوم على موعد السداد`
    }, {
      type: 'debt_due_soon',
      debt_id: doc.id,
      days_left: daysLeft.toString(),
      amount: debt.remainingAmount.toString()
    });
  });
});
```

### 3. فحص المخزون المنخفض (كل 6 ساعات)

```javascript
// كل 6 ساعات
cron.schedule('0 */6 * * *', async () => {
  console.log('🔍 فحص المخزون المنخفض...');
  
  // جلب المنتجات من Firestore
  const products = await admin.firestore()
    .collection('products')
    .get();
  
  // تجميع المنتجات المنخفضة حسب client_uid
  const lowStockByClient = {};
  
  products.forEach(doc => {
    const product = doc.data();
    const remaining = product.remainingQuantity || 0;
    const minimum = product.minQuantity || 0;
    
    if (remaining <= minimum && remaining > 0) {
      const clientUid = product.clientUid;
      
      if (!lowStockByClient[clientUid]) {
        lowStockByClient[clientUid] = [];
      }
      lowStockByClient[clientUid].push({
        id: doc.id,
        name: product.name,
        remaining: remaining,
        minimum: minimum
      });
    }
  });
  
  // إرسال إشعار لكل عميل
  for (const [clientUid, products] of Object.entries(lowStockByClient)) {
    await sendFCMNotification(clientUid, {
      title: 'مخزون منخفض 📦',
      body: `${products.length} منتج تحتاج إلى تعبئة`
    }, {
      type: 'low_stock',
      count: products.length.toString(),
      products: JSON.stringify(products.map(p => p.name))
    });
  }
});
```

### 4. فحص الاشتراكات المنتهية (كل يوم الساعة 8 صباحاً)

```javascript
// كل يوم الساعة 8 صباحاً
cron.schedule('0 8 * * *', async () => {
  console.log('🔍 فحص الاشتراكات المنتهية...');
  
  const now = new Date();
  
  // 1. المنتهية اليوم
  const expiredToday = await db.query(`
    SELECT * FROM clients 
    WHERE DATE(activation_expires_at) = DATE(?)
    AND status = 'active'
  `, [now]);
  
  for (const client of expiredToday) {
    // تحديث الحالة
    await db.query('UPDATE clients SET status = ? WHERE id = ?', ['expired', client.id]);
    await admin.firestore()
      .collection('clients')
      .doc(client.firebase_uid)
      .update({ status: 'expired' });
    
    // إرسال إشعار
    await sendFCMNotification(client.firebase_uid, {
      title: 'انتهى اشتراكك ❌',
      body: 'اشتراكك انتهى، يرجى التجديد'
    }, {
      type: 'subscription_expired'
    });
  }
  
  // 2. ستنتهي خلال 5 أيام
  const fiveDaysLater = new Date();
  fiveDaysLater.setDate(fiveDaysLater.getDate() + 5);
  
  const expiringSoon = await db.query(`
    SELECT * FROM clients 
    WHERE activation_expires_at <= ?
    AND activation_expires_at > ?
    AND status = 'active'
  `, [fiveDaysLater, now]);
  
  for (const client of expiringSoon) {
    const daysLeft = Math.ceil(
      (new Date(client.activation_expires_at) - now) / (1000 * 60 * 60 * 24)
    );
    
    await sendFCMNotification(client.firebase_uid, {
      title: 'اشتراكك ينتهي قريباً ⏰',
      body: `باقي ${daysLeft} أيام على انتهاء اشتراكك`
    }, {
      type: 'subscription_expiring_soon',
      days_left: daysLeft.toString()
    });
  }
});
```

---

## 🔐 Security & Authentication

### Middleware للـ Endpoints

```javascript
// فقط Admin يمكنه إرسال الإشعارات
const requireAdmin = async (req, res, next) => {
  const token = req.headers.authorization?.split('Bearer ')[1];
  
  if (!token) {
    return res.status(401).json({ error: 'No token provided' });
  }
  
  try {
    // التحقق من الـ token
    const decoded = await verifyAdminToken(token);
    req.user = decoded;
    next();
  } catch (error) {
    res.status(401).json({ error: 'Unauthorized' });
  }
};

// استخدام
app.post('/api/notifications/send-to-user', requireAdmin, async (req, res) => {
  // ...
});
```

---

## 📦 Dependencies المطلوبة

### Node.js
```json
{
  "dependencies": {
    "firebase-admin": "^12.0.0",
    "express": "^4.18.0",
    "node-cron": "^3.0.3",
    "dotenv": "^16.0.0"
  }
}
```

### PHP (إذا كان Backend PHP)
```json
{
  "require": {
    "kreait/firebase-php": "^7.0",
    "guzzlehttp/guzzle": "^7.5"
  }
}
```

---

## 🔑 Firebase Service Account Key

### كيفية الحصول عليه:
1. اذهب إلى Firebase Console
2. Project Settings > Service Accounts
3. اضغط "Generate new private key"
4. احفظ الملف `serviceAccountKey.json`
5. **لا ترفعه على Git!** (أضفه في `.gitignore`)

### استخدامه:
```javascript
const admin = require('firebase-admin');
const serviceAccount = require('./serviceAccountKey.json');

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount)
});
```

---

## 🧪 Testing

### اختبار من Postman/cURL

```bash
# إرسال إشعار لمستخدم واحد
curl -X POST http://localhost:3000/api/notifications/send-to-user \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -d '{
    "user_id": "firebase_uid_here",
    "notification": {
      "title": "اختبار",
      "body": "هذا اختبار"
    },
    "data": {
      "type": "general"
    }
  }'
```

---

## ✅ Deliverables المطلوبة

أريد منك:

1. **الكود الكامل** للـ:
   - Helper function: `sendFCMNotification()`
   - Helper function: `sendFCMToMultiple()`
   - Helper function: `sendFCMToAll()`
   - Endpoint: `POST /api/notifications/send-to-user`
   - Endpoint: `POST /api/notifications/send-to-multiple`
   - Endpoint: `POST /api/notifications/send-to-all`
   - تعديل endpoint: `PUT /clients/{id}/update-status` (إضافة إرسال إشعار)

2. **Cron Jobs** (اختياري):
   - فحص الديون المتأخرة (كل ساعة)
   - فحص مواعيد السداد (كل يوم)
   - فحص المخزون (كل 6 ساعات)
   - فحص الاشتراكات (كل يوم)

3. **Documentation**:
   - كيفية تثبيت Dependencies
   - كيفية إعداد Service Account
   - أمثلة cURL للاختبار

---

## 📝 ملاحظات مهمة

1. **FCM Token** محفوظ في Firestore في field `fcm_token`
2. **data.type** مهم جداً - التطبيق يعتمد عليه لمعالجة الإشعار
3. استخدم **multicast** لإرسال لعدة مستخدمين بكفاءة (حتى 500 token/request)
4. **Error handling** ضروري - بعض الـ tokens قد تكون قديمة أو غير صالحة
5. **Log** جميع الإشعارات المرسلة للـ debugging

---

## 🎯 المطلوب الآن:

ابدأ ببناء:
1. Helper functions للإرسال
2. Endpoints الثلاثة
3. تعديل endpoint تحديث الحالة
4. (اختياري) Cron jobs

استخدم Firebase Admin SDK للتواصل مع Firestore و FCM.

**ابدأ الآن!** 🚀

---

