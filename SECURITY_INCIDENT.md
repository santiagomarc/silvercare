# 🔐 SECURITY INCIDENT - API Key Exposure

## ⚠️ What Happened

Your Google Maps API key was accidentally committed to GitHub in this file:
`android/app/src/main/AndroidManifest.xml`

**Exposed Key:** `AIzaSyAwMFPNbW4j41JVhrm-ULVr8puapSZWmuo`

Google Cloud detected this and sent you a security warning.

---

## ✅ Steps Taken to Fix

### 1. **Removed Hardcoded Key from Git**
- Changed `AndroidManifest.xml` to use placeholder: `${GOOGLE_MAPS_API_KEY}`
- Key now loaded from `local.properties` (which is in `.gitignore`)

### 2. **Updated Build Configuration**
- Modified `android/app/build.gradle.kts` to read from `local.properties`
- Key is injected at build time, never committed to git

### 3. **Created Template File**
- Created `android/local.properties.template` as reference
- Shows developers what to add to their local file

---

## 🚨 CRITICAL: What YOU Must Do NOW

### Step 1: Delete the Exposed Key (IMMEDIATELY)

1. Go to https://console.cloud.google.com/
2. Select project: **silvercare-app-3102**
3. Navigate to: **APIs & Services → Credentials**
4. Find key: `AIzaSyAwMFPNbW4j41JVhrm-ULVr8puapSZWmuo`
5. Click the **DELETE** button (not edit, DELETE)
6. Confirm deletion

**Why?** This key is public on GitHub. Anyone can use it and rack up charges on your account.

### Step 2: Create a NEW API Key (with restrictions)

1. Click **"Create Credentials" → "API Key"**
2. **IMMEDIATELY** click **"Edit API key"** or **"Restrict key"**
3. Set these restrictions:

   **Application restrictions:**
   - Select: **Android apps**
   - Click **"Add an item"**
   - Package name: `com.example.silvercare`
   - Get SHA-1 fingerprint:
     ```bash
     keytool -list -v -keystore ~/.android/debug.keystore -alias androiddebugkey -storepass android -keypass android
     ```
   - Copy the SHA-1 and paste it

   **API restrictions:**
   - Select: **Restrict key**
   - Check ONLY: **Maps SDK for Android**

4. Click **Save**
5. Copy the new API key

### Step 3: Add New Key to local.properties

1. Open this file (create if doesn't exist):
   ```
   android/local.properties
   ```

2. Add this line (replace with your NEW key):
   ```properties
   GOOGLE_MAPS_API_KEY=AIzaSyC_YOUR_NEW_KEY_HERE
   ```

3. **DO NOT commit this file!** (It's already in `.gitignore`)

### Step 4: Remove Key from Git History

The old key is still in your git history. To completely remove it:

```bash
# Navigate to your repo
cd c:/Users/rose/Desktop/code/DART/silvercare

# Remove the commit with the exposed key from history
git filter-branch --force --index-filter \
  "git rm --cached --ignore-unmatch android/app/src/main/AndroidManifest.xml" \
  --prune-empty --tag-name-filter cat -- --all

# Or use BFG Repo-Cleaner (easier):
# Download from: https://rtyley.github.io/bfg-repo-cleaner/
# Run: java -jar bfg.jar --replace-text api-keys.txt

# Force push to GitHub (WARNING: rewrites history)
git push origin --force --all
```

**Alternative (Simpler):** Since the key will be deleted anyway, you can just commit the fix and move on. The old key in history won't work after deletion.

---

## 📋 Checklist

- [ ] **DELETE old API key** from Google Cloud Console
- [ ] **CREATE new API key** with restrictions (Android apps + SHA-1)
- [ ] **ADD new key** to `android/local.properties`
- [ ] **TEST** that app builds with new key
- [ ] **COMMIT** the security fixes (without the actual key)
- [ ] **(Optional)** Clean git history with filter-branch or BFG

---

## 🔒 How to Prevent This in the Future

### For API Keys:
1. **NEVER** hardcode API keys in source files
2. **ALWAYS** use `local.properties` or environment variables
3. **CHECK** `.gitignore` includes `local.properties`
4. **USE** placeholder values in committed files: `${VARIABLE_NAME}`

### For Secrets:
1. Use `android/key.properties` for signing keys (already in `.gitignore`)
2. Use environment variables for CI/CD
3. Use secret management services for production
4. Use pre-commit hooks to scan for secrets

### Git Pre-Commit Hook (Optional):
```bash
#!/bin/sh
# .git/hooks/pre-commit
if git diff --cached | grep -i "AIza"; then
    echo "ERROR: Google API key detected in commit!"
    exit 1
fi
```

---

## 📞 Next Steps

1. **Delete the old key RIGHT NOW** ⚠️
2. Create new restricted key
3. Test the app builds successfully
4. Commit and push the security fixes

**Estimated time:** 10-15 minutes

---

**Created:** November 17, 2025  
**Status:** 🔴 CRITICAL - Immediate Action Required
