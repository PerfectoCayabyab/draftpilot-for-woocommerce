=== CopyPilot for WooCommerce ===
Contributors: perfectocayabyab
Tags: woocommerce, ai, product description, seo, copywriting
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI product copywriter for WooCommerce. Generate descriptions and SEO meta with Google Gemini — nothing is published until you approve it.

== Description ==

CopyPilot writes product copy for your WooCommerce store using Google Gemini, with a human-in-the-loop review queue: every AI draft lands in a **Review queue** where you compare it side-by-side with the current copy and click **Approve** or **Reject**. Nothing touches your live store until you approve it.

**What it generates**

* Product description (clean HTML, 120–220 words)
* Short description
* SEO title (≤ 60 characters)
* Meta description (120–155 characters) — written to Yoast SEO or Rank Math automatically when either is active

**Why merchants like it**

* **Review before publish.** AI drafts never go live on their own.
* **Grounded in your product data.** Prompts are built from the product's real name, price, categories, tags, attributes, and SKU — the model is instructed not to invent specifications.
* **Your voice.** Pick a tone preset (professional, friendly, premium, playful, minimal), add brand voice notes, and choose the output language.
* **Bulk-friendly.** Select multiple products and generate copy for all of them with a progress indicator.
* **Free to run.** Works with the Gemini API free tier — bring your own API key.

Built by [Perfecto II Cayabyab](https://perfectocayabyab.com/).

== External services ==

This plugin connects to the **Google Gemini API** (generativelanguage.googleapis.com) to generate product copy. When you click Generate, the following data is sent to Google: the product's name, price, categories, tags, attributes, SKU, and existing description, together with your tone/brand-voice settings. No data is sent anywhere until you click Generate, and no customer or order data is ever sent.

This service is provided by Google: [Terms of Service](https://ai.google.dev/gemini-api/terms), [Privacy Policy](https://policies.google.com/privacy).

You need your own (free) Gemini API key from [Google AI Studio](https://aistudio.google.com/apikey).

== Installation ==

1. Install and activate WooCommerce.
2. Upload the plugin ZIP via **Plugins → Add New → Upload Plugin**, then activate it.
3. Go to **WooCommerce → CopyPilot → Settings** and paste your Gemini API key.
4. Open the **Products** tab, select products, choose a tone, and click **Generate**.
5. Review each draft in the **Review queue** and approve or reject it.

== Frequently Asked Questions ==

= Does the AI publish copy automatically? =

No. Every generated draft goes to the review queue. Copy is only applied to a product when you click Approve.

= Which SEO plugins are supported? =

Approved SEO titles and meta descriptions are written to Yoast SEO and Rank Math fields automatically when either plugin is active. They are also stored in CopyPilot's own meta keys.

= Is the Gemini API free? =

Google offers a free tier for the Gemini API that is sufficient for typical catalog sizes. You can create a key at Google AI Studio.

= What happens to my data if I delete the plugin? =

Deleting the plugin removes the drafts table, all plugin options, and CopyPilot's post meta. Approved copy already applied to products remains, since it is part of your products.

== Screenshots ==

1. Products tab — select products, pick fields and tone, generate in bulk.
2. Review queue — side-by-side current vs proposed copy with Approve/Reject.
3. Settings — API key, model, tone, brand voice, language.

== Changelog ==

= 1.0.0 =
* Initial release: Gemini-powered generation for descriptions, short descriptions, SEO titles and meta descriptions; review queue with approve/reject; tone presets, brand voice and language settings; Yoast SEO and Rank Math integration.
