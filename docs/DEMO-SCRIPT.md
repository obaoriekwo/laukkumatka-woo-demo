# 5-minute demo script

Start the store before the meeting (`docker compose up -d`), and open these tabs:
the homepage, a product page, the cart, `wp-admin`, and a terminal.

**1. Context (30 s)**
"I looked at laukkumatka.fi before this meeting and built a small demo store to show how I would approach the work. It uses WooCommerce with a Storefront child theme, plus a custom plugin for the luggage-specific features."

**2. Homepage (45 s)**
- Point out the trust bar: free delivery over 140 €, 1–3 days, returns, the Seppälä shop. These are the reasons to buy from a small Finnish shop instead of Amazon.
- Category tiles, the "Lento tulossa?" (flying soon?) teaser, and sale badges that show the actual saving (−22 %).

**3. Product page (60 s)**
- Open *Kaamos Cabin*. Expand **"Sopii käsimatkatavaraksi"** (fits as cabin luggage) and show the airline table.
- The spec line, the large −/+ buttons (easy to tap on a phone) and the reassurance list under the button.
- Mention that it was tested on a phone viewport and with keyboard focus.

**4. Finder (30 s)**
- Open *Sopiiko koneeseen?*, choose **Ryanair – istuimen alle** (under the seat), and show the results.
- "It's a plain link, so it can go into a blog post or a newsletter: `?lentoyhtio=ryanair_free`."

**5. Cart (30 s)**
- Add a bag and show the progress bar: "Lisää vielä X €…" (add X € more…). The threshold comes from the real shipping settings.

**6. Admin + catalogue quality (60 s)**
- WooCommerce → **Kaupan tarkastus** (Store audit) → Run. Show the High issues.
- "The first row is the same kind of problem I found on your live site: the size in the URL doesn't match the title."
- In the terminal: `wp laukku audit --severity=high` and `wp laukku fits 55x40x23`.

**7. Wrap-up (30 s)**
- Show `docs/STORE-AUDIT.md`: "These are the quick wins and bigger items I'd suggest. I'd be happy to start with content and catalogue fixes, then move to front-end work."
- "AI tools: I used them to speed up the code and the Finnish copy, and I checked everything myself. I'd use them the same way to clean up 5,000 product descriptions."

## Likely questions – short answers
- **Why a child theme?** Parent-theme updates keep working. All changes are made with hooks, not copied templates.
- **Why Storefront and not Elementor?** It's the clearest way to show how WooCommerce works under the hood. The plugin works with any theme, Elementor included.
- **Will it work with 5,000 products?** Cabin fit is calculated in advance and stored as post meta; the audit runs from the CLI and can export a CSV.
- **What would you do first in the real store?** The quick wins in the audit, then the size and duplicate-listing clean-up, because that matters most for luggage buyers.
