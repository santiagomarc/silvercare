# SilverCare Cloud Functions

This directory contains Firebase Cloud Functions for the SilverCare SOS Alert System.

## Functions

### 1. `sendSOSNotification`
- **Trigger**: Firestore onCreate in `fcm_notifications` collection
- **Purpose**: Processes FCM notification queue and sends high-priority push notifications to caregivers
- **Features**:
  - High-priority Android notifications with custom channel
  - iOS critical alerts support
  - Automatic vibration and sound
  - Error handling and retry logic

### 2. `cleanupOldAlerts`
- **Trigger**: Scheduled daily
- **Purpose**: Deletes resolved SOS alerts older than 30 days
- **Helps**: Keep database clean and reduce storage costs

### 3. `cleanupProcessedNotifications`
- **Trigger**: Scheduled daily
- **Purpose**: Deletes processed FCM notifications older than 7 days
- **Helps**: Maintain notification queue hygiene

## Setup

1. Install dependencies:
```bash
cd functions
npm install
```

2. Deploy to Firebase:
```bash
firebase deploy --only functions
```

3. Test locally with emulator:
```bash
npm run serve
```

## Environment Requirements

- Node.js 18 or higher
- Firebase CLI installed (`npm install -g firebase-tools`)
- Firebase project initialized

## Usage

The Flutter app automatically triggers these functions by:
1. Creating documents in `fcm_notifications` collection (triggers `sendSOSNotification`)
2. Cloud Scheduler runs cleanup functions daily

## Monitoring

View function logs:
```bash
firebase functions:log
```

Or view in Firebase Console > Functions > Logs
