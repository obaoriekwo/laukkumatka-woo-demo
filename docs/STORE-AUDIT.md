# Laukkumatka.fi – quick store review

*Prepared by Oba Oriekwo before the work trial, November 2026.*

**Method:** I read through the public homepage's HTML and content (menus, metadata, product listings, footer). I have not tested speed or visuals on real devices yet, and I'd like to do that during the trial with PageSpeed Insights, Lighthouse and a real phone. Items are ranked by **impact ÷ effort**.

## What already works well
- The foundations are in place: meta description, Open Graph and Twitter tags, a canonical URL, robots tags and Finnish locale (`fi_FI`).
- Strong selling points: free delivery over 140 €, shipping in 1–3 working days, 14-day returns, instalment payments, and a real shop in Jyväskylä.
- A large range (5,000+ products) with clear top-level categories.
- Useful content already exists (flight rules, packing list and jet lag guides) and can be built on for SEO.

## Quick wins (hours, not days)

| # | Finding | Why it matters | Suggested fix |
|---|---|---|---|
| 1 | Homepage metadata includes blog-template leftovers ("Written by Tero", "Time to read: Less than a minute") | These can appear in link previews and Google results, and look unprofessional | Turn off author and reading-time output for pages in the SEO plugin or theme settings |
| 2 | Footer copyright says **© 2011–2024** | Makes the shop look out of date | Output the year automatically (done in the demo theme) |
| 3 | Typos: "**llmainen** toimitus" (lowercase L instead of I), "Seuraa **Zalandoa**:" | Hurts trust, and the Zalando text was probably copied from a template | Fix the text; search the site for other copied template text |
| 4 | The Facebook link points to **laukkumatka.eu**, but the shop is **.fi** | Brand consistency; the link may be outdated | Check and update the social links |
| 5 | The headings "TOIMITUSTAVAT" and "MAKSUTAVAT" have no content under them, and some links go to `#` | Hurts accessibility; screen readers announce empty headings and dead links | Add content (payment and shipping logos plus text) or remove them |
| 6 | Image alt texts repeat ("Ilmainen toimitus", "Suomalainen verkkokauppa") | Hurts accessibility and image search | Write alt text that describes each image; use empty `alt=""` for decorative images |
| 7 | Blog dates use the DD/MM/YYYY format | Finnish users expect `6.11.2026` | Set WordPress's date format to `j.n.Y` |

## Catalogue quality (biggest SEO impact)

| # | Finding | Why it matters | Suggested fix |
|---|---|---|---|
| 8 | **The size in the title and the URL slug disagree**, e.g. a Travel Hack bag whose slug says *45x36x20cm* while the title says *40x30x20cm* | For luggage, size is the main reason people buy. A wrong size means returns, disappointed customers and confusing signals for Google. | Run the **Store audit** (`wp laukku audit`) over the catalogue, fix the slugs and add 301 redirects |
| 9 | **Duplicate listings**, e.g. two "Cabin Max 24L Koda – 40x30x20cm" products at different URLs | They compete with each other in search results and split the reviews | Merge them into **one variable product** with colour variations, and redirect the old URLs |
| 10 | Very few reviews (5.0 from 3 reviews) | Social proof drives conversion | Send an automated review request email after delivery, and show reviews on product cards |
| 11 | Dimensions are mostly only in the product title | Shoppers can't filter by size, and the cabin-size checker can't run | Fill in the WooCommerce dimension fields during a content sprint, starting with luggage categories (good work for the trial) |

## Navigation and usability

| # | Finding | Suggested fix |
|---|---|---|
| 12 | The category menu appears **4+ times** in the page HTML (header, sidebar, footer, mobile) | Use one menu with responsive CSS, or load the mobile menu only when it is opened. This makes pages lighter and faster. |
| 13 | The filter list is very long and has repeated labels ("Muut", "Kaikki", "Swing CK", "Kattoteltat 1") | Clean up attribute terms. Show only the filters that matter for each category (size, volume, wheels, colour, price). |
| 14 | Product cards show **raw URLs as visible text** and have **two "Lisää ostoskoriin" links** | Fix the card template: one button per card, and an `aria-label` that includes the product name |
| 15 | Category URLs repeat the parent slug at every level and get long | Shorten the category base. Make any changes with 301 redirects so existing rankings aren't lost. |
| 16 | Nothing answers "will this bag fit as cabin luggage?" | Add a **cabin-size checker and finder** (built in the demo plugin) and promote it on the homepage and in blog posts about flight rules |

## Performance (to measure first)
The page loads Google Tag Manager, the Facebook pixel, Elementor and a lot of category markup. During the trial I would:
1. Measure Core Web Vitals (LCP, INP, CLS) with PageSpeed Insights on mobile, for the homepage, a category page and a product page.
2. Check image sizes and formats (WebP/AVIF) and lazy-loading below the fold.
3. Check that tracking scripts load only after cookie consent (also a GDPR requirement).
4. Remove unused Elementor widgets and CSS.

## SEO content ideas
- Category pages need 2–3 sentences of real description, e.g. "Käsimatkatavarat – laukut, jotka mahtuvat Finnairin, Ryanairin ja Wizz Airin mittoihin." (Cabin bags that fit Finnair, Ryanair and Wizz Air limits.)
- Turn the existing PDF guides into HTML pages (Google indexes HTML better than PDFs) and link them to the matching categories.
- Product structured data: add brand, dimensions and a return policy (done in the demo plugin).
- LocalBusiness structured data and a Google Business Profile for the Jyväskylä shop.

## Where AI tools can help
- Write first drafts of product descriptions and category texts in Finnish from supplier data, then have a person review them.
- Generate alt text drafts for images in bulk.
- Spot inconsistencies such as size mismatches and duplicates, and catch typos across 5,000 products.
- Speed up front-end work: CSS, accessibility checks and test scripts.
