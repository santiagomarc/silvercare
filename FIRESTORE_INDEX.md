# Firestore Index Configuration

Create these indexes in Firebase Console → Firestore Database → Indexes → Composite:

---

### Health Data Index

Collection ID: `health_data`
Fields:
1. `elderlyId` (Ascending)
2. `type` (Ascending) 
3. `measuredAt` (Descending)

---

### Elderly Checklist Index

Collection ID: `elderly_checklists`
Fields:
1. `elderlyId` (Ascending)
2. `dueDate` (Ascending)

---

## How to create the indexes:

1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select your project
3. Navigate to Firestore Database
4. Click on "Indexes" tab
5. Click "Create Index" for each index defined above
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
    },
    {
      "collectionGroup": "elderly_checklists",
      "queryScope": "COLLECTION",
      "fields": [
        {
          "fieldPath": "elderlyId",
          "order": "ASCENDING"
        },
        {
          "fieldPath": "dueDate",
          "order": "ASCENDING"
        }
      ]
    }
  ]
}
```

Then run: `firebase deploy --only firestore:indexes`
