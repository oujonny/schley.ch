#!/usr/bin/env bash
# Holt den offiziellen PDF.js-Viewer von Mozilla (vorgebaute "dist"-Version)
# nach static/pdfjs/ und hängt unser Theme (/css/pdfjs-theme.css) ein.
# Das Ergebnis wird ins Repo eingecheckt und von unserem Server ausgeliefert –
# das Skript braucht es nur für ein Update:
#
#   1. PDFJS_VERSION + PDFJS_SHA256 anpassen
#      (Releases: https://github.com/mozilla/pdf.js/releases)
#   2. bash scripts/fetch-pdfjs.sh
#   3. static/pdfjs/ committen
set -euo pipefail

PDFJS_VERSION="6.5.438"
PDFJS_SHA256="fcbe9ba21d9911843e3502adc178a7f549a524a4055f887bedc942e5490c6ba1"

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DEST="$ROOT/static/pdfjs"
STAMP="$DEST/.version"

if [[ -f "$STAMP" && "$(cat "$STAMP")" == "$PDFJS_VERSION" ]]; then
  echo "PDF.js $PDFJS_VERSION ist bereits vorhanden."
  exit 0
fi

TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

curl -fsSL -o "$TMP/pdfjs.zip" \
  "https://github.com/mozilla/pdf.js/releases/download/v${PDFJS_VERSION}/pdfjs-${PDFJS_VERSION}-dist.zip"
echo "${PDFJS_SHA256}  $TMP/pdfjs.zip" | sha256sum -c -

rm -rf "$DEST"
mkdir -p "$DEST"
unzip -q "$TMP/pdfjs.zip" -d "$DEST"

# Nicht benötigt: Source-Maps, Debug-Tools und das Beispiel-PDF.
find "$DEST" -name '*.map' -delete
rm -f "$DEST/web/debugger.mjs" "$DEST/web/debugger.css" \
      "$DEST/web/compressed.tracemonkey-pldi-09.pdf"

# Nur die Landessprachen + Englisch behalten (spart ~3 MB). locale.json wird
# passend gekürzt; andere Browsersprachen fallen auf Englisch zurück.
KEEP_LOCALES="de en-US fr it"
for dir in "$DEST"/web/locale/*/; do
  name="$(basename "$dir")"
  [[ " $KEEP_LOCALES " == *" $name "* ]] || rm -rf "$dir"
done
python3 - "$DEST/web/locale/locale.json" $KEEP_LOCALES <<'PY'
import json, sys
path, keep = sys.argv[1], set(sys.argv[2:])
data = json.load(open(path))
data = {k: v for k, v in data.items() if v.split("/")[0] in keep}
json.dump(data, open(path, "w"), indent=1, sort_keys=True)
PY

# Unser Theme nach viewer.css einbinden.
sed -i 's|</head>|<link rel="stylesheet" href="/css/pdfjs-theme.css">\n</head>|' \
  "$DEST/web/viewer.html"
grep -q 'pdfjs-theme.css' "$DEST/web/viewer.html"

echo "$PDFJS_VERSION" > "$STAMP"
echo "PDF.js $PDFJS_VERSION installiert ($(du -sh "$DEST" | cut -f1))."
