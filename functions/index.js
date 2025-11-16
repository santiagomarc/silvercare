const functions = require('firebase-functions');
const admin = require('firebase-admin');

// Initialize Firebase Admin SDK
admin.initializeApp();

/**
 * Cloud Function: Send SOS FCM Notification
 * Triggered when a document is created in 'fcm_notifications' collection
 * This function processes the notification queue and sends high-priority FCM messages
 */
exports.sendSOSNotification = functions.firestore
  .document('fcm_notifications/{notificationId}')
  .onCreate(async (snap, context) => {
    const notificationData = snap.data();

    // Check if already processed
    if (notificationData.processed) {
      console.log('Notification already processed:', context.params.notificationId);
      return null;
    }

    try {
      console.log('Processing SOS notification:', context.params.notificationId);

      const fcmToken = notificationData.to;
      const notification = notificationData.notification;
      const data = notificationData.data;
      const androidConfig = notificationData.android;
      const apnsConfig = notificationData.apns;

      // Build FCM message
      const message = {
        token: fcmToken,
        notification: {
          title: notification.title,
          body: notification.body,
        },
        data: data,
        android: {
          priority: 'high',
          notification: {
            channelId: androidConfig.notification.channelId,
            sound: androidConfig.notification.sound,
            priority: androidConfig.notification.priority,
            defaultSound: androidConfig.notification.defaultSound,
            defaultVibrateTimings: androidConfig.notification.defaultVibrateTimings,
            vibrateTimingsMillis: androidConfig.notification.vibrateTimingsMillis,
            color: '#FF0000', // Red color for emergency
            tag: 'sos_alert',
            sticky: true,
          },
        },
        apns: apnsConfig,
      };

      // Send the FCM message
      const response = await admin.messaging().send(message);
      console.log('✅ Successfully sent FCM message:', response);

      // Mark notification as processed
      await snap.ref.update({
        processed: true,
        processedAt: admin.firestore.FieldValue.serverTimestamp(),
        fcmResponse: response,
      });

      return { success: true, messageId: response };
    } catch (error) {
      console.error('❌ Error sending FCM notification:', error);

      // Update notification with error
      await snap.ref.update({
        processed: true,
        processedAt: admin.firestore.FieldValue.serverTimestamp(),
        error: error.message,
      });

      return { success: false, error: error.message };
    }
  });

/**
 * Cloud Function: Clean up old SOS alerts
 * Runs daily to delete resolved alerts older than 30 days
 */
exports.cleanupOldAlerts = functions.pubsub
  .schedule('every 24 hours')
  .onRun(async (context) => {
    const db = admin.firestore();
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);

    try {
      // Query for old resolved alerts
      const oldAlerts = await db
        .collection('sos_alerts')
        .where('status', '==', 'resolved')
        .where('resolvedAt', '<', admin.firestore.Timestamp.fromDate(thirtyDaysAgo))
        .get();

      console.log(`Found ${oldAlerts.size} old alerts to delete`);

      // Delete in batches
      const batch = db.batch();
      oldAlerts.docs.forEach((doc) => {
        batch.delete(doc.ref);
      });

      await batch.commit();
      console.log(`✅ Deleted ${oldAlerts.size} old alerts`);

      return { deleted: oldAlerts.size };
    } catch (error) {
      console.error('❌ Error cleaning up old alerts:', error);
      return { error: error.message };
    }
  });

/**
 * Cloud Function: Clean up processed FCM notifications
 * Runs daily to delete processed notifications older than 7 days
 */
exports.cleanupProcessedNotifications = functions.pubsub
  .schedule('every 24 hours')
  .onRun(async (context) => {
    const db = admin.firestore();
    const sevenDaysAgo = new Date();
    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);

    try {
      // Query for old processed notifications
      const oldNotifications = await db
        .collection('fcm_notifications')
        .where('processed', '==', true)
        .where('processedAt', '<', admin.firestore.Timestamp.fromDate(sevenDaysAgo))
        .get();

      console.log(`Found ${oldNotifications.size} old notifications to delete`);

      // Delete in batches
      const batch = db.batch();
      oldNotifications.docs.forEach((doc) => {
        batch.delete(doc.ref);
      });

      await batch.commit();
      console.log(`✅ Deleted ${oldNotifications.size} old notifications`);

      return { deleted: oldNotifications.size };
    } catch (error) {
      console.error('❌ Error cleaning up old notifications:', error);
      return { error: error.message };
    }
  });
