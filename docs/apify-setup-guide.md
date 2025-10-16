# Apify Setup Guide for Google Reviews

This guide explains how to set up Apify to fetch Google Maps reviews for your business.

## Why Apify?

Apify provides a simpler alternative to Google's official APIs:
- ✅ No Google Business Profile ownership required
- ✅ No OAuth2 setup needed
- ✅ Can fetch more than 5 reviews (up to 50+)
- ✅ Simple API token authentication
- ⚠️ Costs per scrape run (see Apify pricing)

## Step 1: Create Apify Account

1. Go to [apify.com](https://apify.com)
2. Sign up for a free account
3. Free tier includes $5 credit/month (sufficient for testing)

## Step 2: Get Your API Token

1. Log into Apify Console
2. Go to **Settings** → **Integrations**
3. Copy your **API Token**
   - Format: `apify_api_1234567890abcdefghijklmnopqrst`
4. Save this token securely

## Step 3: Get Your Google Maps Place URL

1. Go to [Google Maps](https://maps.google.com)
2. Search for your business (e.g., "Bravo Jewelers")
3. Click on your business listing
4. Copy the full URL from your browser
   - Example: `https://www.google.com/maps/place/Bravo+Jewelers/@40.7580,-73.9855,17z/data=...`
   - OR just use the Place ID if you have it: `ChIJN1t_tDeuEmsRUsoyG83frY4`

## Step 4: Configure the Plugin

1. Go to WordPress Admin → **SEO Pages** → **Settings**
2. Click on the **Review Integration** tab
3. Enter your credentials:
   - **Apify API Token**: Paste the token from Step 2
   - **Google Maps Place URL**: Paste the URL from Step 3
   - **Maximum Reviews**: Choose how many reviews to fetch (default: 50)
4. Click **Save Changes**

The plugin will securely encrypt and store your Apify API token in the database.

## Step 5: Test the Integration

1. Generate a page with `review_section` in the block order
2. Check `wp-content/debug.log` for:
   - `[Apify Reviews] Starting review fetch`
   - `[Apify Reviews] Actor run started: [run_id]`
   - `[Apify Reviews] Fetched X reviews`

## How It Works

1. **Plugin calls Apify API** → Starts a scraper run
2. **Apify scrapes Google Maps** → Collects reviews (takes 30-120 seconds)
3. **Plugin polls for completion** → Checks status every 5 seconds
4. **Results stored in database** → Cached for 30 days

## Pricing Estimate

**Apify Pricing:**
- Free tier: $5 credit/month
- Each scrape run costs ~$0.10-0.25 (depends on # of reviews)
- Plugin caches for 30 days, so ~1-2 runs/month typical

**Example:**
- 50 reviews per run
- Cache lasts 30 days
- Cost: ~$0.20/month (well within free tier)

## Troubleshooting

### "Actor run timed out"
- The scraper is taking too long (>2 minutes)
- Try reducing `MAX_REVIEWS` constant
- Check Apify console for run details

### "No reviews found in results"
- Verify your `PLACE_URL` is correct
- Check if your business has public reviews on Google Maps
- View the dataset in Apify console to see raw data

### "Failed to start actor run"
- Check your `APIFY_API_TOKEN` is correct
- Verify you have Apify credits remaining
- Check Apify status page for outages

## Alternative: Use Different Actor

The default actor is `compass/crawler-google-places`. You can use alternatives:

```php
// Try a different actor if default doesn't work
const APIFY_ACTOR_ID = 'dtrungtin/google-maps-scraper';
```

Popular alternatives:
- `compass/crawler-google-places` (default, most reliable)
- `dtrungtin/google-maps-scraper` (faster, less detailed)
- `maxcopell/google-maps` (budget option)

## Support

- **Apify Docs**: [docs.apify.com](https://docs.apify.com)
- **Actor Docs**: Search for "Google Maps Scraper" in Apify Store
- **Plugin Logs**: Check `wp-content/debug.log` for `[Apify Reviews]` entries
