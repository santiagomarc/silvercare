# Firestore Index Configuration

Create this index in Firebase Console → Firestore Database → Indexes → Composite:

Collection ID: `health_data`
Fields:
1. `elderlyId` (Ascending)
2. `type` (Ascending) 
3. `measuredAt` (Descending)

## How to create the index:

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select your project
3. Navigate to Firestore Database
4. Click on "Indexes" tab
5. Click "Create Index"
6. Add the fields as shown above
7. Click "Create"

**Or use this Firebase CLI command:**

```bash
# Add this to your firestore.indexes.json file:
{
  "indexes": [
    {
      "collectionGroup": "health_data",
      "queryScope": "COLLECTION",
      "fields": [
        {
          "fieldPath": "elderlyId",
          "order": "ASCENDING"
        },
        {
          "fieldPath": "type", 
          "order": "ASCENDING"
        },
        {
          "fieldPath": "measuredAt",
          "order": "DESCENDING"
        }
      ]
    }
  ]
}
```

Then run: `firebase deploy --only firestore:indexes`