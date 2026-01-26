# Meta Credentials Setup Guide

This guide explains how to obtain the necessary credentials to use the Meta Provider (Instagram Messaging API + Facebook Messenger API) with the WhatsApp HSM Adapter.

## Overview

The Meta Provider uses Meta's unified Messenger Platform, which powers both Instagram Direct Messages and Facebook Messenger. You'll need to set up a Facebook App and connect it to a Facebook Page to get started.

## Prerequisites

- A Facebook account
- A Facebook Page (you'll create or use an existing one)
- An Instagram Professional or Business account (for Instagram messaging)
- Admin access to both the Facebook Page and Instagram account

## Step 1: Create a Meta (Facebook) App

1. Go to [Meta for Developers](https://developers.facebook.com/)
2. Click **"My Apps"** in the top right corner
3. Click **"Create App"**
4. Select **"Business"** as the app type
5. Fill in the app details:
   - **App Name**: Choose a descriptive name (e.g., "My Business Messaging")
   - **App Contact Email**: Your email address
   - **Business Account**: Select or create a business account
6. Click **"Create App"**

**Result**: You now have a Meta App with an **App ID** and **App Secret**.

### Finding Your App ID and App Secret

1. In your app dashboard, go to **Settings** → **Basic**
2. Copy the **App ID** (this is your `META_APP_ID`)
3. Click **"Show"** next to **App Secret** and copy it (this is your `META_APP_SECRET`)

## Step 2: Add Messenger Product to Your App

1. In your app dashboard, scroll down to **"Add Products"**
2. Find **"Messenger"** and click **"Set Up"**
3. This will add the Messenger product to your app

## Step 3: Create or Connect a Facebook Page

You need a Facebook Page to send and receive messages.

### Option A: Create a New Page

1. Go to [facebook.com/pages/create](https://www.facebook.com/pages/create)
2. Follow the prompts to create a new Page
3. Choose a category and fill in the details

### Option B: Use an Existing Page

1. Make sure you have admin access to the Page
2. Note the Page name for the next steps

### Finding Your Page ID

1. Go to your Facebook Page
2. Click **"About"** in the left sidebar
3. Scroll down to find **"Page ID"** (this is your `META_PAGE_ID`)

**Alternative method**:

1. Go to `https://www.facebook.com/YOUR_PAGE_NAME`
2. The Page ID is in the URL or can be found in the page source

## Step 4: Generate a Page Access Token

1. In your Meta App dashboard, go to **Messenger** → **Settings**
2. Scroll down to **"Access Tokens"**
3. Click **"Add or Remove Pages"**
4. Select your Facebook Page and grant the necessary permissions:
   - `pages_messaging`
   - `pages_read_engagement`
   - `pages_manage_metadata`
5. Click **"Generate Token"** next to your Page
6. Copy the token (this is your `META_PAGE_ACCESS_TOKEN`)

**Important**: This is a short-lived token. You'll need to convert it to a long-lived token.

### Converting to a Long-Lived Token

Run this command (replace the placeholders):

```bash
curl -X GET "https://graph.facebook.com/v21.0/oauth/access_token?grant_type=fb_exchange_token&client_id=YOUR_APP_ID&client_secret=YOUR_APP_SECRET&fb_exchange_token=YOUR_SHORT_LIVED_TOKEN"
```

The response will contain a long-lived token (valid for 60 days):

```json
{
  "access_token": "YOUR_LONG_LIVED_TOKEN",
  "token_type": "bearer",
  "expires_in": 5183944
}
```

Use this long-lived token as your `META_PAGE_ACCESS_TOKEN`.

## Step 5: Connect Instagram Account (Optional)

If you want to use Instagram messaging:

1. Make sure your Instagram account is a **Professional** or **Business** account
2. In your Meta App dashboard, go to **Instagram** → **Settings**
3. Click **"Add or Remove Instagram Accounts"**
4. Connect your Instagram Professional account to your Facebook Page
5. Grant the necessary permissions:
   - `instagram_basic`
   - `instagram_manage_messages`
   - `pages_show_list`

**Note**: The same Page Access Token works for both Facebook Messenger and Instagram.

## Step 6: Set Up Webhooks

1. In your Meta App dashboard, go to **Messenger** → **Settings**
2. Scroll down to **"Webhooks"**
3. Click **"Add Callback URL"**
4. Enter your webhook URL: `https://your-domain.com/webhook/meta`
5. Enter a **Verify Token** (create a random string, this is your `META_VERIFY_TOKEN`)
6. Click **"Verify and Save"**

### Subscribe to Webhook Events

After adding the callback URL, subscribe to these events:

**For Messenger**:

- `messages`
- `messaging_postbacks`
- `message_deliveries`
- `message_reads`

**For Instagram**:

- `messages`
- `messaging_postbacks`
- `message_deliveries`
- `message_reads`

## Step 7: Configure Environment Variables

Add the following to your `.env` file:

```bash
# Meta Configuration (Instagram + Facebook Messenger)
META_PAGE_ACCESS_TOKEN=your_long_lived_page_access_token
META_APP_ID=your_app_id
META_APP_SECRET=your_app_secret
META_PAGE_ID=your_page_id
META_VERIFY_TOKEN=your_custom_verify_token
META_API_VERSION=v21.0
```

## Step 8: Test Your Setup

### Test Webhook Verification

Meta will send a GET request to verify your webhook. Make sure your server responds correctly.

### Test Sending a Message

Use the admin panel or API to send a test message:

```bash
curl -X POST https://your-domain.com/api/messages/send \
  -H "Content-Type: application/json" \
  -d '{
    "provider": "meta",
    "recipient": "IGSID_OR_PSID",
    "message": {
      "text": "Hello from Meta Provider!"
    }
  }'
```

### Test Receiving a Message

1. Send a message to your Facebook Page or Instagram account
2. Check your webhook logs to see if the message was received
3. Verify the message appears in your admin panel

## Important Notes

### Instagram-Scoped ID (IGSID)

- Instagram uses **IGSID** (Instagram-Scoped ID) to identify users
- You get the IGSID from incoming webhook messages
- IGSIDs are numeric strings (e.g., "1234567890")

### Page-Scoped ID (PSID)

- Facebook Messenger uses **PSID** (Page-Scoped ID) to identify users
- You get the PSID from incoming webhook messages
- PSIDs are numeric strings (e.g., "9876543210")

### 24-Hour Messaging Window

- You can only send messages to users within **24 hours** of their last message
- After 24 hours, you need to use **Message Tags** (limited use cases)
- Plan your messaging strategy accordingly

### Rate Limits

- Meta has rate limits per Page (not per app)
- Default: ~200 requests per minute
- Monitor your usage to avoid hitting limits

### Permissions Review

- Some permissions require **App Review** by Meta
- `pages_messaging` and `instagram_manage_messages` typically require review
- Submit your app for review once you're ready for production

## Troubleshooting

### "Invalid OAuth access token"

- Your token may have expired (short-lived tokens expire quickly)
- Generate a new long-lived token
- Make sure you're using the Page Access Token, not a User Access Token

### "Account not eligible for messages" (Error 36103)

- The Instagram account may not be a Professional/Business account
- Make sure the account is connected to your Facebook Page
- Check that the account has accepted your Page's message request

### Webhook not receiving messages

- Verify your webhook URL is publicly accessible (use ngrok for local testing)
- Check that you've subscribed to the correct webhook events
- Verify the webhook signature validation is working correctly

### "Feature not available" (Error 2534068)

- Some features may not be available in your region
- Check Meta's documentation for feature availability
- Ensure your app has the necessary permissions

## Additional Resources

- [Meta for Developers Documentation](https://developers.facebook.com/docs/)
- [Messenger Platform API Reference](https://developers.facebook.com/docs/messenger-platform)
- [Instagram Messaging API Reference](https://developers.facebook.com/docs/messenger-platform/instagram)
- [Webhook Reference](https://developers.facebook.com/docs/messenger-platform/webhooks)
- [Page Access Tokens](https://developers.facebook.com/docs/pages/access-tokens)

## Security Best Practices

1. **Never commit tokens to version control**

   - Use `.env` files (excluded from git)
   - Use environment variables in production

2. **Rotate tokens regularly**

   - Long-lived tokens expire after 60 days
   - Set up a process to refresh tokens before expiry

3. **Validate webhook signatures**

   - Always verify the `X-Hub-Signature-256` header
   - Use constant-time comparison to prevent timing attacks

4. **Use HTTPS for webhooks**

   - Meta requires HTTPS for webhook URLs
   - Use a valid SSL certificate

5. **Limit token permissions**
   - Only request the permissions you need
   - Review permissions regularly

## Support

If you encounter issues:

1. Check the [Meta Developer Community](https://developers.facebook.com/community/)
2. Review the [Messenger Platform Changelog](https://developers.facebook.com/docs/messenger-platform/changelog)
3. Contact Meta Developer Support through the app dashboard

---

**Last Updated**: January 2025  
**API Version**: v21.0
