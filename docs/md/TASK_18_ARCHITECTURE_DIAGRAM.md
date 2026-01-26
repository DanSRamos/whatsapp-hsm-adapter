# Task 18: Admin Panel Multi-Provider Architecture

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     Admin Panel Frontend                         │
│                      (index.html)                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌────────────────────────────────────────────────────────┐    │
│  │              Provider Selection Dropdown                │    │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐            │    │
│  │  │WhatsApp  │  │Instagram │  │Messenger │            │    │
│  │  │(Infobip) │  │  (Meta)  │  │  (Meta)  │            │    │
│  │  └──────────┘  └──────────┘  └──────────┘            │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                   │
│  ┌────────────────────────────────────────────────────────┐    │
│  │           Provider-Specific Form Fields                 │    │
│  ├────────────────────────────────────────────────────────┤    │
│  │                                                          │    │
│  │  WhatsApp Fields:                                       │    │
│  │  • Template Selection                                   │    │
│  │  • Phone Number                                         │    │
│  │  • Language                                             │    │
│  │  • Dynamic Parameters                                   │    │
│  │                                                          │    │
│  │  Instagram Fields:                                      │    │
│  │  • IGSID Input                                          │    │
│  │  • Message Type (Text/Media/Multiple Images)           │    │
│  │  • Text/Media/Image URLs                               │    │
│  │  • 24h Window Warning                                   │    │
│  │                                                          │    │
│  │  Messenger Fields:                                      │    │
│  │  • PSID Input                                           │    │
│  │  • Message Type (Text/Media)                           │    │
│  │  • Text/Media URLs                                      │    │
│  │  • 24h Window Warning                                   │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                   │
│  ┌────────────────────────────────────────────────────────┐    │
│  │              Messages Display Panel                      │    │
│  ├────────────────────────────────────────────────────────┤    │
│  │  Provider Filter: [All ▼] [WhatsApp] [Instagram] [Messenger]│
│  │                                                          │    │
│  │  ┌──────────────────────────────────────────────┐      │    │
│  │  │ 📱 351961725398  [WhatsApp]    10:30:00     │      │    │
│  │  │ Message text...                              │      │    │
│  │  └──────────────────────────────────────────────┘      │    │
│  │                                                          │    │
│  │  ┌──────────────────────────────────────────────┐      │    │
│  │  │ 📷 1234567890  [Instagram]     10:31:00     │      │    │
│  │  │ Instagram message...                         │      │    │
│  │  └──────────────────────────────────────────────┘      │    │
│  │                                                          │    │
│  │  ┌──────────────────────────────────────────────┐      │    │
│  │  │ 💬 9876543210  [Messenger]     10:32:00     │      │    │
│  │  │ Messenger message...                         │      │    │
│  │  └──────────────────────────────────────────────┘      │    │
│  └────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ HTTP POST/GET
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Backend API (api.php)                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌────────────────────────────────────────────────────────┐    │
│  │              sendMessage() Router                       │    │
│  │                                                          │    │
│  │  Receives: { provider: "whatsapp|instagram|messenger" } │    │
│  │                                                          │    │
│  │  Routes to:                                             │    │
│  │  • sendWhatsAppMessage()                               │    │
│  │  • sendMetaMessage($config, $input, 'instagram')       │    │
│  │  • sendMetaMessage($config, $input, 'messenger')       │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                   │
│  ┌────────────────────────────────────────────────────────┐    │
│  │         sendWhatsAppMessage()                           │    │
│  │  • Validates template and recipient                     │    │
│  │  • Builds Infobip payload                              │    │
│  │  • Sends to Infobip API                                │    │
│  │  • Returns message ID and status                        │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                   │
│  ┌────────────────────────────────────────────────────────┐    │
│  │         sendMetaMessage($platform)                      │    │
│  │  • Validates recipient ID (IGSID/PSID)                 │    │
│  │  • Validates message type                              │    │
│  │  • Builds Meta Graph API payload                       │    │
│  │  • Handles text/media/multiple images                  │    │
│  │  • Sends to Meta Graph API                             │    │
│  │  • Returns message ID and status                        │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                   │
│  ┌────────────────────────────────────────────────────────┐    │
│  │         getMessages()                                   │    │
│  │  • Reads messages from storage                          │    │
│  │  • Adds provider information                           │    │
│  │  • Adds IGSID for Instagram                            │    │
│  │  • Adds PSID for Messenger                             │    │
│  │  • Returns formatted messages                           │    │
│  └────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
         │                                    │
         │                                    │
         ▼                                    ▼
┌──────────────────┐              ┌──────────────────────┐
│  Infobip API     │              │  Meta Graph API      │
│  (WhatsApp)      │              │  v21.0               │
│                  │              │  (Instagram +        │
│  Endpoint:       │              │   Messenger)         │
│  /whatsapp/1/    │              │                      │
│  message/        │              │  Endpoint:           │
│  template        │              │  /{page-id}/         │
│                  │              │  messages            │
└──────────────────┘              └──────────────────────┘
```

## Data Flow

### 1. Send WhatsApp Message

```
User Input (Frontend)
  ↓
  provider: "whatsapp"
  template: "template_name"
  recipient: "351961725398"
  parameters: ["value1", "value2"]
  ↓
sendMessage() Router
  ↓
sendWhatsAppMessage()
  ↓
Infobip API
  ↓
Response: { messageId, status }
  ↓
Display Success/Error
```

### 2. Send Instagram Message

```
User Input (Frontend)
  ↓
  provider: "instagram"
  recipient: "1234567890" (IGSID)
  messageType: "text"
  text: "Hello!"
  ↓
sendMessage() Router
  ↓
sendMetaMessage($config, $input, 'instagram')
  ↓
Meta Graph API v21.0
  POST /{page-id}/messages
  {
    recipient: { id: "1234567890" },
    message: { text: "Hello!" }
  }
  ↓
Response: { message_id }
  ↓
Display Success/Error
```

### 3. Send Messenger Message

```
User Input (Frontend)
  ↓
  provider: "messenger"
  recipient: "9876543210" (PSID)
  messageType: "media"
  mediaType: "image"
  mediaUrl: "https://..."
  ↓
sendMessage() Router
  ↓
sendMetaMessage($config, $input, 'messenger')
  ↓
Meta Graph API v21.0
  POST /{page-id}/messages
  {
    recipient: { id: "9876543210" },
    message: {
      attachment: {
        type: "image",
        payload: { url: "https://..." }
      }
    }
  }
  ↓
Response: { message_id }
  ↓
Display Success/Error
```

### 4. Load and Filter Messages

```
Frontend Request
  ↓
GET api.php?action=get_messages
  ↓
getMessages()
  ↓
Read messages.json
  ↓
Add provider info
  ↓
Response: [
  { provider: "whatsapp", from: "...", text: "..." },
  { provider: "instagram", igsid: "...", text: "..." },
  { provider: "messenger", psid: "...", text: "..." }
]
  ↓
Store in allMessages
  ↓
Apply Filter (All/WhatsApp/Instagram/Messenger)
  ↓
Render Filtered Messages with Badges
```

## Component Interaction

### Frontend Components

```
┌─────────────────────────────────────────────────────┐
│              JavaScript Functions                    │
├─────────────────────────────────────────────────────┤
│                                                       │
│  onProviderChange()                                  │
│    ├─ Shows/hides WhatsApp fields                   │
│    ├─ Shows/hides Instagram fields                  │
│    └─ Shows/hides Messenger fields                  │
│                                                       │
│  onInstagramMessageTypeChange()                      │
│    ├─ Shows text fields                             │
│    ├─ Shows media fields                            │
│    └─ Shows multiple images fields                  │
│                                                       │
│  onMessengerMessageTypeChange()                      │
│    ├─ Shows text fields                             │
│    └─ Shows media fields                            │
│                                                       │
│  sendMessage()                                       │
│    ├─ Validates provider-specific fields            │
│    ├─ Builds provider-specific payload              │
│    └─ Sends to backend API                          │
│                                                       │
│  filterMessages()                                    │
│    ├─ Reads filter selection                        │
│    ├─ Filters allMessages array                     │
│    └─ Calls renderMessages()                        │
│                                                       │
│  renderMessages()                                    │
│    ├─ Adds provider badges                          │
│    ├─ Adds provider icons                           │
│    ├─ Shows IGSID/PSID                              │
│    └─ Applies color coding                          │
│                                                       │
│  loadMessages()                                      │
│    ├─ Fetches from backend                          │
│    ├─ Stores in allMessages                         │
│    └─ Calls filterMessages()                        │
└─────────────────────────────────────────────────────┘
```

### Backend Components

```
┌─────────────────────────────────────────────────────┐
│              PHP Functions                           │
├─────────────────────────────────────────────────────┤
│                                                       │
│  sendMessage()                                       │
│    ├─ Validates provider parameter                  │
│    ├─ Routes to sendWhatsAppMessage()               │
│    ├─ Routes to sendMetaMessage('instagram')        │
│    └─ Routes to sendMetaMessage('messenger')        │
│                                                       │
│  sendWhatsAppMessage()                               │
│    ├─ Validates template and recipient              │
│    ├─ Builds Infobip payload                        │
│    ├─ Calls Infobip API                             │
│    └─ Returns formatted response                    │
│                                                       │
│  sendMetaMessage($platform)                          │
│    ├─ Validates recipient ID                        │
│    ├─ Validates message type                        │
│    ├─ Builds Meta payload (text/media/images)       │
│    ├─ Calls Meta Graph API                          │
│    └─ Returns formatted response                    │
│                                                       │
│  getMessages()                                       │
│    ├─ Reads messages.json                           │
│    ├─ Adds provider field                           │
│    ├─ Adds IGSID/PSID fields                        │
│    └─ Returns formatted array                       │
└─────────────────────────────────────────────────────┘
```

## Configuration Flow

```
Environment Variables
  ↓
  META_PAGE_ACCESS_TOKEN
  META_PAGE_ID
  ↓
getenv() in api.php
  ↓
$config array
  ↓
Passed to sendMetaMessage()
  ↓
Used in Meta Graph API calls
```

## Error Handling Flow

```
User Input
  ↓
Frontend Validation
  ├─ Provider-specific required fields
  ├─ Format validation (IGSID/PSID)
  ├─ Image count validation
  └─ URL validation
  ↓
Backend Validation
  ├─ Provider validation
  ├─ Recipient validation
  ├─ Message type validation
  └─ Credentials validation
  ↓
API Call
  ├─ Success → Return message ID
  └─ Error → Return detailed error
  ↓
Display to User
  ├─ Success alert (green)
  └─ Error alert (red)
```

## Message Storage Format

```json
{
  "messages": [
    {
      "from": "351961725398",
      "to": "351927587119",
      "text": "WhatsApp message",
      "messageId": "msg_123",
      "timestamp": "2026-01-19T10:30:00Z",
      "provider": "whatsapp"
    },
    {
      "from": "1234567890",
      "igsid": "1234567890",
      "text": "Instagram message",
      "messageId": "msg_456",
      "timestamp": "2026-01-19T10:31:00Z",
      "provider": "instagram"
    },
    {
      "from": "9876543210",
      "psid": "9876543210",
      "text": "Messenger message",
      "messageId": "msg_789",
      "timestamp": "2026-01-19T10:32:00Z",
      "provider": "messenger"
    }
  ]
}
```

## UI State Management

```
Provider Selection State
  ↓
  whatsapp → Show WhatsApp fields
  instagram → Show Instagram fields
  messenger → Show Messenger fields
  ↓
Message Type State (Instagram)
  ↓
  text → Show text textarea
  media → Show media type + URL
  multiple-images → Show image URLs textarea
  ↓
Message Type State (Messenger)
  ↓
  text → Show text textarea
  media → Show media type + URL
  ↓
Filter State
  ↓
  all → Show all messages
  whatsapp → Show WhatsApp messages only
  instagram → Show Instagram messages only
  messenger → Show Messenger messages only
```
