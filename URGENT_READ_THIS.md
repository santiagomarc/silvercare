## 🚨 IMMEDIATE ACTION REQUIRED

### Your Google Maps API key was exposed on GitHub!

**Follow these steps RIGHT NOW:**

---

### 1️⃣ **DELETE the Exposed Key (5 minutes)**

1. Go to: https://console.cloud.google.com/apis/credentials?project=silvercare-app-3102
2. Find key: `AIzaSyAwMFPNbW4j41JVhrm-ULVr8puapSZWmuo`
3. Click **DELETE** (not edit - DELETE!)
4. Confirm deletion

---

### 2️⃣ **Create a NEW Restricted Key (5 minutes)**

1. Click **"Create Credentials" → "API Key"**
2. Immediately click **"Restrict key"**
3. Set restrictions:
   - **Application restrictions:** Android apps
     - Package: `com.example.silvercare`
     - SHA-1: Get with:
       ```bash
       keytool -list -v -keystore ~/.android/debug.keystore -alias androiddebugkey -storepass android -keypass android
       ```
   - **API restrictions:** Maps SDK for Android ONLY
4. Save and copy the new key

---

### 3️⃣ **Add New Key to local.properties**

1. Open (or create): `android/local.properties`
2. Add this line:
   ```
   GOOGLE_MAPS_API_KEY=YOUR_NEW_KEY_HERE
   ```
3. Save the file

---

### 4️⃣ **Commit the Security Fix**

```bash
git add .
git commit -m "Security: Move API key to local.properties (not committed)"
git push
```

---

### ✅ **Files Changed:**

- `android/app/src/main/AndroidManifest.xml` - Now uses placeholder `${GOOGLE_MAPS_API_KEY}`
- `android/app/build.gradle.kts` - Reads from local.properties
- `android/local.properties` - Add your NEW key here (NOT committed to git)

---

**Time to fix:** ~15 minutes  
**See full details:** `SECURITY_INCIDENT.md`
