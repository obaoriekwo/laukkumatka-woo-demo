"""Build Finnish (fi) translations for the plugin and the child theme.

Workflow:
  1. wp i18n make-pot <dir> <dir>/languages/<domain>.pot   (extract strings)
  2. python3 tools/build_translations.py                    (this script: .po + .mo)
  3. wp i18n make-php <dir>/languages                       (optional, WP 6.5+ fast .l10n.php)

Requires: pip install polib
"""
from pathlib import Path

import polib

ROOT = Path(__file__).resolve().parent.parent / "wp-content"

FI = {
    # ---- plugin -----------------------------------------------------------
    "under-seat bag (free)": "istuimen alle (ilmainen)",
    "cabin bag": "käsimatkatavara",
    "Cabin size": "Käsimatkatavarakoko",
    "Fits as cabin bag: %s": "Sopii käsimatkatavaraksi: %s",
    "Check-in size – too big for most cabin limits": "Ruumakoko – liian suuri useimpiin käsimatkatavararajoihin",
    "Bag size: %s (incl. wheels and handles)": "Laukun koko: %s (sis. pyörät ja kahvat)",
    "Airline hand-luggage size limits compared with this bag": "Lentoyhtiöiden käsimatkatavaroiden kokorajat verrattuna tähän laukkuun",
    "Airline": "Lentoyhtiö",
    "Limit": "Raja",
    "Fits?": "Sopiiko?",
    "Yes": "Kyllä",
    "No": "Ei",
    "Airlines change their rules and fares differ – always confirm with your airline before flying.": "Lentoyhtiöiden säännöt muuttuvat ja lipputyypit eroavat – tarkista aina omalta lentoyhtiöltäsi ennen matkaa.",
    "Find more bags that fit": "Etsi lisää sopivia laukkuja",
    "Will it fit on the plane?": "Sopiiko laukku koneeseen?",
    "Choose airline and ticket type": "Valitse lentoyhtiö ja lipputyyppi",
    "— Select —": "— Valitse —",
    "…or enter your own size limit (cm)": "…tai syötä oma kokoraja (cm)",
    "Length": "Pituus",
    "Width": "Leveys",
    "Height": "Korkeus",
    "Show bags that fit": "Näytä sopivat laukut",
    "Bags that fit inside %s": "Laukut, jotka mahtuvat mittoihin %s",
    "Bags that fit %1$s as %2$s": "Laukut, jotka sopivat: %1$s, %2$s",
    "No bags found for that size. Try a larger limit or a different ticket type.": "Tähän kokoon sopivia laukkuja ei löytynyt. Kokeile suurempaa rajaa tai toista lipputyyppiä.",
    "Add %s more to get free delivery!": "Lisää ostoskoriin vielä %s, niin toimitus on ilmainen!",
    "Your order ships for free!": "Tilauksesi toimitetaan ilmaiseksi!",
    "Progress towards free delivery": "Edistyminen kohti ilmaista toimitusta",
    "— Not specified —": "— Ei määritetty —",
    "Hard shell": "Kovakuorinen",
    "Soft shell (fabric)": "Pehmeä (kangas)",
    "No wheels": "Ei pyöriä",
    "2 wheels": "2 pyörää",
    "4 spinner wheels": "4 kääntyvää pyörää",
    "Luggage": "Laukku",
    "Volume (litres)": "Tilavuus (litraa)",
    "Shown on product cards and in structured data.": "Näytetään tuotekorteissa ja rakenteisessa datassa.",
    "Wheels": "Pyörät",
    "Shell type": "Kuoren tyyppi",
    "Warranty (years)": "Takuu (vuotta)",
    "Tip: fill in length, width and height on the Shipping tab – the cabin-size checker uses them.": "Vinkki: täytä pituus, leveys ja korkeus Toimitus-välilehdelle – käsimatkatavaratarkistin käyttää niitä.",
    "%s cm": "%s cm",
    "%s L": "%s l",
    "%d wheel": ("%d pyörä", "%d pyörää"),
    "Volume": "Tilavuus",
    "Warranty": "Takuu",
    "%d year": ("%d vuosi", "%d vuotta"),
    "Travel tools": "Matkatyökalut",
    "Luggage-store features from the Laukku Travel Tools plugin.": "Laukkukaupan ominaisuudet Laukku Travel Tools -lisäosasta.",
    "Cabin-size badge": "Käsimatkatavaramerkki",
    "Show a \"Cabin size\" badge on product cards": "Näytä \"Käsimatkatavarakoko\"-merkki tuotekorteissa",
    "Check cabin size for": "Tarkista käsimatkatavarakoko osastoille",
    "Categories that contain bags. Leave empty to check every product that has dimensions.": "Osastot, joissa on laukkuja. Jätä tyhjäksi, niin kaikki mitoitetut tuotteet tarkistetaan.",
    "Finder page": "Hakusivu",
    "Page that contains the [laukku_cabin_finder] shortcode. Linked from product pages.": "Sivu, jolla on [laukku_cabin_finder]-lyhytkoodi. Linkitetään tuotesivuilta.",
    "— None —": "— Ei mitään —",
    "Free-shipping progress bar": "Ilmaisen toimituksen edistymispalkki",
    "Show in cart, mini-cart and checkout": "Näytä ostoskorissa, minikorissa ja kassalla",
    "Fallback threshold (€)": "Vararaja (€)",
    "Used only if no Free shipping method with a minimum amount is configured.": "Käytetään vain, jos ilmaista toimitustapaa vähimmäissummalla ei ole asetettu.",
    "Return window (days)": "Palautusaika (päivää)",
    "Added to product structured data as a MerchantReturnPolicy. 0 = disabled.": "Lisätään tuotteen rakenteiseen dataan MerchantReturnPolicy-tietona. 0 = pois käytöstä.",
    "No product image": "Ei tuotekuvaa",
    "No price – product cannot be bought": "Ei hintaa – tuotetta ei voi ostaa",
    "Sale price is not lower than regular price": "Alennushinta ei ole normaalihintaa alempi",
    "Size in title and in URL disagree": "Otsikon ja URL-osoitteen koot eroavat",
    "Size in title disagrees with the dimension fields": "Otsikon koko eroaa mittakentistä",
    "Duplicate product title (possible duplicate listing)": "Sama tuotenimi useammin (mahdollinen tuplatuote)",
    "Missing dimensions – cabin checker cannot run": "Mitat puuttuvat – käsimatkatavaratarkistus ei toimi",
    "Main image has no alt text": "Pääkuvalta puuttuu alt-teksti",
    "No short description": "Lyhyt kuvaus puuttuu",
    "Description under 150 characters": "Kuvaus alle 150 merkkiä",
    "Title over 70 characters (truncated in Google)": "Otsikko yli 70 merkkiä (katkeaa Googlessa)",
    "Only in the default \"Uncategorized\" category": "Vain oletusosastossa \"Luokittelematon\"",
    "Store audit": "Kaupan tarkastus",
    "You are not allowed to do that.": "Sinulla ei ole oikeutta tähän.",
    "Checks every published product for problems that hurt search visibility, customer trust and sales. Fix the red ones first.": "Tarkistaa jokaisen julkaistun tuotteen ongelmien varalta, jotka heikentävät hakunäkyvyyttä, asiakkaiden luottamusta ja myyntiä. Korjaa punaiset ensin.",
    "Run audit": "Aja tarkastus",
    "Run again": "Aja uudelleen",
    "Last run %s ago.": "Ajettu viimeksi %s sitten.",
    "Tip: on big catalogues use WP-CLI: wp laukku audit --format=csv > audit.csv": "Vinkki: isoissa valikoimissa käytä WP-CLI:tä: wp laukku audit --format=csv > audit.csv",
    "All": "Kaikki",
    "High": "Korkea",
    "Medium": "Keskitaso",
    "Low": "Matala",
    "Severity": "Vakavuus",
    "Product": "Tuote",
    "SKU": "Tuotekoodi",
    "Issue": "Ongelma",
    "Detail": "Lisätieto",
    "No issues found. Nice!": "Ongelmia ei löytynyt. Hienoa!",
    "Laukku Travel Tools requires WooCommerce to be installed and active.": "Laukku Travel Tools vaatii, että WooCommerce on asennettu ja käytössä.",
    "Settings": "Asetukset",
    # ---- theme ------------------------------------------------------------
    "Decrease quantity": "Vähennä määrää",
    "Increase quantity": "Lisää määrää",
    "Shop by category": "Selaa osastoittain",
    "Our picks for your next trip": "Poimintamme seuraavalle matkallesi",
    "On sale now": "Nyt tarjouksessa",
    "New arrivals": "Uutuudet",
    "Flying soon? Avoid the gate fee.": "Lento tulossa? Vältä lisämaksu portilla.",
    "Pick your airline and ticket type – we show every bag that fits the cabin limits.": "Valitse lentoyhtiö ja lipputyyppi – näytämme kaikki laukut, jotka mahtuvat käsimatkatavaroiden rajoihin.",
    "Find a bag that fits": "Löydä sopiva laukku",
    "Free delivery over %s": "Ilmainen toimitus yli %s tilauksiin",
    "Ships in 1–3 working days from Jyväskylä": "Toimitus Jyväskylästä 1–3 arkipäivässä",
    "14-day return right": "14 päivän palautusoikeus",
    "Visit our shop in Seppälä, Jyväskylä": "Myymälä Jyväskylän Seppälässä",
    "Visit the shop": "Tervetuloa myymälään",
    "Opening hours": "Aukioloajat",
    "Online shop": "Verkkokauppa",
    "Open 24/7. Orders ship from our Jyväskylä warehouse.": "Avoinna 24/7. Tilaukset lähtevät Jyväskylän varastoltamme.",
    "In the K-Citymarket Seppälä building": "Samassa rakennuksessa K-Citymarket Seppälän kanssa",
    "Mon–Fri": "ma–pe",
    "Sat": "la",
    "Sun": "su",
    "Save %d %%": "Säästä %d %%",
    "Delivered in 1–3 working days": "Toimitus 1–3 arkipäivässä",
    "Pick up and try at our Jyväskylä shop": "Nouda ja sovita myymälässämme Jyväskylässä",
    "Specifications": "Tekniset tiedot",
    "Unofficial demo store made for a job application – not a real shop. Orders are not delivered and nothing is charged.": "Epävirallinen demokauppa työhakemusta varten – ei oikea verkkokauppa. Tilauksia ei toimiteta eikä mitään veloiteta.",
}

TARGETS = [
    ("plugins/laukku-travel-tools/languages", "laukku-travel-tools", "laukku-travel-tools-fi"),
    # Child themes load <locale>.mo (no domain prefix) from their languages folder.
    ("themes/laukku-storefront-child/languages", "laukku-child", "fi"),
]

for folder, domain, out_name in TARGETS:
    pot = polib.pofile(str(ROOT / folder / f"{domain}.pot"))
    po = polib.POFile()
    po.metadata = {
        "Project-Id-Version": domain,
        "Language": "fi",
        "MIME-Version": "1.0",
        "Content-Type": "text/plain; charset=UTF-8",
        "Content-Transfer-Encoding": "8bit",
        "Plural-Forms": "nplurals=2; plural=(n != 1);",
        "X-Domain": domain,
    }
    missing = []
    for entry in pot:
        tr = FI.get(entry.msgid)
        new = polib.POEntry(msgid=entry.msgid, msgid_plural=entry.msgid_plural, occurrences=entry.occurrences, comment=entry.comment)
        if entry.msgid_plural:
            if isinstance(tr, tuple):
                new.msgstr_plural = {0: tr[0], 1: tr[1]}
            else:
                missing.append(entry.msgid)
        elif tr:
            new.msgstr = tr
        else:
            missing.append(entry.msgid)
        po.append(new)
    po.save(str(ROOT / folder / f"{out_name}.po"))
    po.save_as_mofile(str(ROOT / folder / f"{out_name}.mo"))
    print(f"{domain}: {len(pot) - len(missing)}/{len(pot)} translated; untranslated (headers/names): {missing[:4]}")
