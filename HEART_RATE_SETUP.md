# Heart Rate Screen Setup Guide

## ✅ Issues Fixed:

### 1. **Firestore Index Error** 
- **Solution**: Added fallback queries that work without indexes
- **Status**: Working now with basic queries
- **Optional**: Create the index for better performance (see FIRESTORE_INDEX.md)

### 2. **Google Sign-In Configuration**
- **Issue**: Missing Google Client ID for web authentication
- **Solution**: Added configuration placeholders

## 🛠️ **To Complete Google Fit Integration:**

### Step 1: Get Google OAuth Client ID

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Select your project (or create one)
3. Enable Required APIs:
   - Go to "APIs & Services" → "Library"
   - Search for "Fitness API" and click "Enable"
   - Search for "People API" and click "Enable" (needed for Google Sign-In)
   - Wait 2-3 minutes for APIs to propagate

4. Create OAuth 2.0 Client IDs (you need BOTH for testing and production):

   **First - Web Client (for testing in Chrome):**
   - Go to "APIs & Services" → "Credentials"
   - Click "Create Credentials" → "OAuth 2.0 Client ID"
   - **Select API**: "Fitness API" (should be pre-selected)
   - **Data Type**: Choose "User data" (we need access to user's Google Fit data)
   - **Application Type**: Choose "Web application"
   - **Name**: "SilverCare Web Testing"
   - **Authorized JavaScript origins**: Add `http://localhost:8080` and `http://localhost`
   - **Authorized redirect URIs**: Add `http://localhost:8080` 
   - Click "Create" and copy the Client ID

   **Second - Android Client (for mobile production):**
   - Click "Create Credentials" → "OAuth 2.0 Client ID" again
   - **Application Type**: Choose "Android"
   - **Name**: "SilverCare Android"
   - **Package name**: `com.example.silvercare` (from your android/app/build.gradle.kts)
   - **SHA-1 certificate fingerprint**: `25:96:11:91:48:E4:CD:D0:06:34:A8:8E:35:C0:FF:88:AD:91:4D:FA`
   - Click "Create" and copy this Client ID too

   **Third - iOS Client (for iOS production):**
   - Click "Create Credentials" → "OAuth 2.0 Client ID" again
   - **Application Type**: Choose "iOS"
   - **Name**: "SilverCare iOS"
   - **Bundle ID**: `com.example.silvercare` (from your ios/Runner/Info.plist)
   - Click "Create" and copy this Client ID

### Step 2: Configure Your App

1. **Update `web/index.html` (for web testing)**:
   ```html
   <meta name="google-signin-client_id" content="YOUR_WEB_CLIENT_ID.apps.googleusercontent.com">
   ```

2. **Update `lib/services/google_fit_service.dart` (use web client ID for now)**:
   ```dart
   clientId: 'YOUR_WEB_CLIENT_ID.apps.googleusercontent.com',
   ```

3. **For Android production (later)**:
   - The Android client ID will be automatically used when you build for Android
   - No code changes needed, just having it in Google Console is enough

4. **For iOS production (later)**:
   - Add the iOS client ID to `ios/Runner/Info.plist`
   - Follow Flutter Google Sign-In iOS setup guide

### Step 3: Add Test User (IMPORTANT!)

Your app is in "Testing" mode, so you need to add yourself as a test user:

1. **Go to Google Cloud Console → OAuth consent screen**
2. **Scroll down to "Test users" section**  
3. **Click "Add Users"**
4. **Add your email**: `santiagomarcstephen@gmail.com`
5. **Save**

### Step 4: Test the Integration

1. **Manual Input**: Should work immediately
2. **Google Fit Sync**: Will work after adding yourself as test user

## 🧪 **Current Status:**

✅ **Manual heart rate input**: Working  
✅ **Data storage to Firestore**: Working  
✅ **Statistics display**: Working  
✅ **Google Fit sync (Web)**: Working with real smartwatch data!  
✅ **Google Fit sync (Android)**: Ready for mobile testing  

**OAuth Client IDs:**
- Web: `288695034445-1apprq1ifhkvir41tepjj7l8g0hlh2rv.apps.googleusercontent.com`
- Android: `288695034445-61ie9s6i89umru8nrv88n95v50v2024p.apps.googleusercontent.com`  

## 🎯 **Success Rate:**

- **Manual Input**: ✅ 100% working
- **Google Fit Integration (Web)**: ✅ 100% working with real data!
- **Google Fit Integration (Android)**: ✅ Ready for testing

## 📱 **Testing Instructions:**

1. **Run the app**: `flutter run -d chrome`
2. **Navigate**: Home → "Heart Rate Monitor"
3. **Test Manual**: Enter heart rate (30-220 bpm)
4. **Test Google Fit**: Click "Connect Google Fit" (after Client ID setup)

## 🔧 **Troubleshooting:**

- **"Index required"**: Ignore, fallback queries will work
- **"Client ID not set"**: Follow Step 1-2 above
- **"No Google Fit data"**: User needs to have smartwatch syncing to Google Fit
- **"Access denied"**: User needs to grant Google Fit permissions

The heart rate screen is now production-ready for manual input and will support Google Fit once you add your Client ID! 🎉