#!/usr/bin/env bash
# End-to-end tes keamanan & fungsi DailyIn (dipakai di CI dan lokal).
#
# PERINGATAN: script ini MENGHAPUS & MENGISI ULANG database tujuan. Jangan arahkan ke database production.
#
# Variabel:
#   BASE_URL   URL aplikasi yang sedang jalan       (default http://127.0.0.1:8099)
#   APP_DIR    folder aplikasi yang dilayani server (default: root repo)
#   DB_DRIVER  pgsql | mysql                        (default pgsql)
#   PGURL      connection string psql               (untuk pgsql)
#   MYSQL_HOST MYSQL_PORT MYSQL_USER MYSQL_PASSWORD MYSQL_DATABASE (untuk mysql)
#   PSQL / MYSQL  path binary client (default psql / mysql)
set -u

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
B="${BASE_URL:-http://127.0.0.1:8099}"
APP="${APP_DIR:-$ROOT}"
DRIVER="${DB_DRIVER:-pgsql}"
PSQL="${PSQL:-psql}"
MYSQL="${MYSQL:-mysql}"
TMP="$(mktemp -d)"
# Git Bash di Windows: curl/php native tidak mengerti path /tmp, pakai bentuk C:/...
command -v cygpath >/dev/null && TMP="$(cygpath -m "$TMP")"
J="$TMP/jar.txt"
trap 'rm -rf "$TMP"; rm -f "$APP/canary.txt"' EXIT

if [ "$DRIVER" = pgsql ]; then
  SQL() { "$PSQL" "$PGURL" -tA -c "$1" | sed 's/\r$//'; }
  UT='"user"'
else
  MY=("$MYSQL" -h "${MYSQL_HOST:-127.0.0.1}" -P "${MYSQL_PORT:-3306}" -u "${MYSQL_USER:-root}")
  [ -n "${MYSQL_PASSWORD:-}" ] && MY+=("-p$MYSQL_PASSWORD")
  SQL() { "${MY[@]}" -N -B --default-character-set=utf8mb4 "$MYSQL_DATABASE" -e "$1" | sed 's/\r$//'; }
  UT='`user`'
fi

PASS=0; FAIL=0
ok()    { echo "PASS  $1"; PASS=$((PASS+1)); }
bad()   { echo "FAIL  $1  -> $2"; FAIL=$((FAIL+1)); }
check() { if [ "$2" = "$3" ]; then ok "$1"; else bad "$1" "got [$2] want [$3]"; fi; }
code()  { curl -s -o /dev/null -w '%{http_code}' "$@"; }
some()  { if [ "${2:-0}" -gt 0 ] 2>/dev/null; then ok "$1"; else bad "$1" "got [${2:-}] want [>0]"; fi; }
token() { curl -s -b "$J" -c "$J" "$1" | grep -o 'name="csrf_token" value="[a-f0-9]*"' | head -1 | sed 's/.*value="//;s/"//'; }

echo "== Reset test DB ($DRIVER) =="
if [ "$DRIVER" = pgsql ]; then
  "$PSQL" "$PGURL" -q -c "DROP SCHEMA public CASCADE; CREATE SCHEMA public;" >/dev/null
  "$PSQL" "$PGURL" -q -v ON_ERROR_STOP=1 -f "$ROOT/database/postgres.sql" >/dev/null
else
  "${MY[@]}" -e "DROP DATABASE IF EXISTS \`$MYSQL_DATABASE\`; CREATE DATABASE \`$MYSQL_DATABASE\`;"
  "${MY[@]}" "$MYSQL_DATABASE" < "$ROOT/webdailyjournal.sql"
  "${MY[@]}" "$MYSQL_DATABASE" < "$ROOT/migrations/002_sessions.sql"
fi
# simulasi password lama md5 untuk tes upgrade otomatis ke bcrypt
SQL "UPDATE $UT SET password = md5('123456')" >/dev/null

echo "== Guest =="
check "index 200" "$(code "$B/")" "200"
check "unknown url 404" "$(code "$B/halaman-tidak-ada")" "404"
check "404 page rendered" "$(curl -s "$B/halaman-tidak-ada" | grep -c 'Halaman yang Anda cari tidak ditemukan')" "1"
check "admin.php redirects guest" "$(code "$B/admin.php?page=article")" "302"
check "admin.php body empty for guest" "$(curl -s "$B/admin.php?page=article" | grep -c 'Tambah Article')" "0"
curl -s -o /dev/null -X POST -d 'hapus=hapus&id=7' "$B/admin.php?page=article"
check "guest POST delete ignored" "$(SQL 'SELECT COUNT(*) FROM article WHERE id=7')" "1"
for f in article.php gallery.php dashboard.php password.php; do
  check "direct $f blocked" "$(code -X POST -d 'hapus=1&id=7' "$B/$f")" "403"
done
check "article_data guest 401" "$(code -X POST "$B/article_data.php")" "401"
check "gallery_data guest 401" "$(code -X POST "$B/gallery_data.php")" "401"
check "ai content guest 401" "$(code -H 'Content-Type: application/json' -d '{"mode":"generate_content","text":"x"}' "$B/ai_helper.php")" "401"
check "ai unknown mode guest 401" "$(code -H 'Content-Type: application/json' -d '{"mode":"x","text":"hack"}' "$B/ai_helper.php")" "401"
check "ai summary unknown id 404" "$(code -H 'Content-Type: application/json' -d '{"mode":"generate_summary","id":999}' "$B/ai_helper.php")" "404"
r=$(curl -s -H 'Content-Type: application/json' -d '{"mode":"generate_summary","id":5}' "$B/ai_helper.php")
case "$r" in *"Google API"*|*"Curl"*|*"API key"*) bad "public summary hides API error" "$r";; *) ok "public summary hides API error";; esac
check "api get_article 404 unknown" "$(code "$B/api/get_article.php?id=999")" "404"
check "api get_article 400 bad id" "$(code "$B/api/get_article.php?id=abc")" "400"
check "index no longer embeds isi" "$(curl -s "$B/" | grep -c 'data-isi=')" "0"

echo "== Login =="
t=$(token "$B/login.php")
check "login without csrf 403" "$(code -b "$J" -c "$J" -d 'user=admin&pass=123456' "$B/login.php")" "403"
r=$(curl -s -o /dev/null -w '%{redirect_url}' -b "$J" -c "$J" --data-urlencode "csrf_token=$t" -d 'user=admin&pass=salah' "$B/login.php")
case "$r" in *login.php?error=1) ok "wrong password rejected";; *) bad "wrong password rejected" "$r";; esac
check "failed login recorded" "$(SQL "SELECT COUNT(*) FROM login_attempts WHERE username='admin'")" "1"
t=$(token "$B/login.php")
r=$(curl -s -o /dev/null -w '%{redirect_url}' -b "$J" -c "$J" --data-urlencode "csrf_token=$t" -d 'user=admin&pass=123456' "$B/login.php")
case "$r" in *admin.php) ok "legacy md5 login works";; *) bad "legacy md5 login works" "$r";; esac
check "md5 upgraded to bcrypt" "$(SQL "SELECT LEFT(password,4) FROM $UT WHERE username='admin'")" '$2y$'
check "successful login clears failures" "$(SQL "SELECT COUNT(*) FROM login_attempts WHERE username='admin'")" "0"

echo "== Admin pages =="
check "LFI page falls back to dashboard" "$(curl -s -b "$J" "$B/admin.php?page=../koneksi" | grep -c 'bi-newspaper')" "1"
check "article_data hlm=abc ok" "$(code -b "$J" -d 'hlm=abc' "$B/article_data.php")" "200"
check "article_data hlm=-5 ok" "$(curl -s -b "$J" -d 'hlm=-5' "$B/article_data.php" | grep -c 'Total article')" "1"
check "article_data hlm=999 ok" "$(curl -s -b "$J" -d 'hlm=999' "$B/article_data.php" | grep -c '<tr>')" "2"
check "no PHP warnings in admin table" "$(curl -s -b "$J" -d 'hlm=1' "$B/article_data.php" | grep -c -E '(Deprecated|Warning|Notice|Fatal error)(</b>)?:')" "0"
check "no PHP warnings on public pages" "$({ curl -s "$B/"; curl -s "$B/artikel/coding"; curl -s "$B/sitemap.xml"; } | grep -c -E '(Deprecated|Warning|Notice|Fatal error)(</b>)?:')" "0"
check "edit modal textarea uses summernote class" "$(curl -s -b "$J" -d 'hlm=1' "$B/article_data.php" | grep -c 'form-control summernote')" "3"
T=$(token "$B/admin.php?page=article")
check "delete without csrf rejected" "$(curl -s -b "$J" -d 'hapus=hapus&id=7' "$B/admin.php?page=article" | grep -c 'CSRF token salah')" "1"
check "article 7 still exists" "$(SQL 'SELECT COUNT(*) FROM article WHERE id=7')" "1"

echo "== Create article (XSS + upload) =="
php -r '$i=imagecreatetruecolor(20,20); imagejpeg($i, $argv[1]);' "$TMP/ok.jpg"
printf '<?php echo "pwned"; ?>' > "$TMP/evil.jpg"
printf 'Caption <b>x</b> \xe2\x9c\xa8' > "$TMP/caption.txt"
ISI='<p onclick="alert(1)">Halo <b>dunia</b></p><script>alert(2)</script><a href="java&#x09;script:alert(3)">x</a><img src=x onerror=alert(4)><iframe src="https://evil.com"></iframe><iframe src="https://www.youtube.com/embed/abc"></iframe>'
curl -s -o /dev/null -b "$J" -F "csrf_token=$T" -F 'simpan=1' --form-string "judul=<script>alert('j')</script> Uji" --form-string "isi=$ISI" -F "summary=<$TMP/caption.txt" -F "gambar=@$TMP/ok.jpg;type=image/jpeg" "$B/admin.php?page=article"
ID=$(SQL "SELECT MAX(id) FROM article")
DBISI=$(SQL "SELECT isi FROM article WHERE id=$ID")
case "$DBISI" in *script*|*onclick*|*onerror*|*evil.com*|*javascript*) bad "isi sanitized" "$DBISI";; *) ok "isi sanitized";; esac
case "$DBISI" in *youtube.com/embed*) ok "youtube iframe kept";; *) bad "youtube iframe kept" "$DBISI";; esac
check "caption emoji stored (20 bytes utf8)" "$(SQL "SELECT OCTET_LENGTH(summary) FROM article WHERE id=$ID")" "20"
check "flash toast after save" "$(curl -s -b "$J" -c "$J" "$B/admin.php?page=article" | grep -c 'toast-body">Simpan data sukses')" "1"
check "flash shown only once" "$(curl -s -b "$J" -c "$J" "$B/admin.php?page=article" | grep -c 'toast-body')" "0"
IMG=$(SQL "SELECT gambar FROM article WHERE id=$ID")
case "$IMG" in 20[0-9]*_[0-9a-f]*.jpg) ok "random upload name";; *) bad "random upload name" "$IMG";; esac
[ -f "$APP/img/$IMG" ] && ok "uploaded file exists" || bad "uploaded file exists" "$IMG"
API=$(curl -s "$B/api/get_article.php?id=$ID")
case "$API" in *'"success":true'*'youtube.com'*) ok "api returns article html";; *) bad "api returns article html" "$API";; esac
# hanya field "isi" yang berupa HTML (judul berupa teks biasa, dipasang lewat textContent)
API_ISI=$(echo "$API" | sed 's/.*"isi":"\(.*\)","isi_artikel".*/\1/')
case "$API_ISI" in *onclick*|*'<script'*|*onerror*) bad "api html sanitized" "$API_ISI";; *) ok "api html sanitized";; esac
PUB=$(curl -s "$B/index.php")
check "index escapes judul" "$(echo "$PUB" | grep -c "<script>alert('j')")" "0"
check "admin table escapes judul" "$(curl -s -b "$J" -d 'hlm=1' "$B/article_data.php" | grep -c "<script>alert('j')")" "0"
before=$(SQL "SELECT COUNT(*) FROM article")
curl -s -o /dev/null -b "$J" -F "csrf_token=$T" -F 'simpan=1' --form-string 'judul=evil' --form-string 'isi=x' -F "gambar=@$TMP/evil.jpg;type=image/jpeg" "$B/admin.php?page=article"
check "fake image rejected" "$(SQL "SELECT COUNT(*) FROM article")" "$before"
check "upload error shown as danger toast" "$(curl -s -b "$J" -c "$J" "$B/admin.php?page=article" | grep -c 'text-bg-danger')" "1"
check "no php in img/" "$(grep -l 'pwned' "$APP"/img/* 2>/dev/null | wc -l | tr -d ' ')" "0"

echo "== Delete article: path traversal =="
echo canary > "$APP/canary.txt"
curl -s -o /dev/null -b "$J" -d "csrf_token=$T&hapus=hapus&id=$ID&gambar=../canary.txt" "$B/admin.php?page=article"
check "article deleted" "$(SQL "SELECT COUNT(*) FROM article WHERE id=$ID")" "0"
[ -f "$APP/canary.txt" ] && ok "traversal file NOT deleted" || bad "traversal file NOT deleted" "canary gone"
[ -f "$APP/img/$IMG" ] && bad "article image deleted" "still there" || ok "article image deleted"
check "flash toast after delete" "$(curl -s -b "$J" -c "$J" "$B/admin.php?page=article" | grep -c 'toast-body">Hapus data sukses')" "1"

echo "== Shared image protection =="
curl -s -o /dev/null -b "$J" -d "csrf_token=$T&hapus=hapus&id=1" "$B/admin.php?page=article"
check "article 1 deleted" "$(SQL "SELECT COUNT(*) FROM article WHERE id=1")" "0"
[ -f "$APP/img/book.jpg" ] && ok "book.jpg kept (used by gallery)" || bad "book.jpg kept" "deleted"

echo "== Gallery =="
TG=$(token "$B/admin.php?page=gallery")
curl -s -o /dev/null -b "$J" -F "csrf_token=$TG" -F 'simpan=simpan' --form-string "judul=O'Reilly', 'x', NOW(), 'y'); DROP TABLE user; --" -F "gambar=@$TMP/ok.jpg;type=image/jpeg" "$B/admin.php?page=gallery"
GID=$(SQL "SELECT MAX(id) FROM gallery")
check "gallery quote stored literally" "$(SQL "SELECT judul FROM gallery WHERE id=$GID")" "O'Reilly', 'x', NOW(), 'y'); DROP TABLE user; --"
check "user table intact" "$(SQL "SELECT COUNT(*) FROM $UT")" "1"
curl -s -o /dev/null -b "$J" -F "csrf_token=$TG" -F 'edit=edit' -F "id=$GID" --form-string "judul=Edit 1' OR '1'='1" "$B/admin.php?page=gallery"
check "gallery edit only one row" "$(SQL "SELECT COUNT(*) FROM gallery WHERE judul LIKE 'Edit 1%'")" "1"
curl -s -o /dev/null -b "$J" -d "csrf_token=$TG&hapus=Hapus&id=$GID&gambar=../canary.txt" "$B/admin.php?page=gallery"
check "gallery delete works" "$(SQL "SELECT COUNT(*) FROM gallery WHERE id=$GID")" "0"
[ -f "$APP/canary.txt" ] && ok "gallery traversal blocked" || bad "gallery traversal blocked" "canary gone"

echo "== Fase 3: halaman artikel, tag, pencarian, SEO =="
HOME_HTML=$(curl -s "$B/")
some "home links to slug url" "$(echo "$HOME_HTML" | grep -c 'href="/artikel/coding"')"
ART=$(curl -s "$B/artikel/coding")
check "article page 200" "$(code "$B/artikel/coding")" "200"
check "article og:title" "$(echo "$ART" | grep -c '<meta property="og:title" content="Coding"')" "1"
check "article canonical" "$(echo "$ART" | grep -c 'rel="canonical" href="http[^"]*/artikel/coding"')" "1"
check "article json-ld" "$(echo "$ART" | grep -c '"@type":"BlogPosting"')" "1"
check "article shows tag" "$(echo "$ART" | grep -c 'href="/tag/coding#article"')" "1"
check "unknown article 404" "$(code "$B/artikel/tidak-ada-sama-sekali")" "404"
check "old id url 301" "$(code "$B/artikel.php?id=5")" "301"
check "old id url -> slug" "$(curl -s -o /dev/null -w '%{redirect_url}' "$B/artikel.php?id=5" | grep -c '/artikel/coding$')" "1"
TAGP=$(curl -s "$B/tag/kampus")
check "tag page 200" "$(code "$B/tag/kampus")" "200"
some "tag page lists tagged" "$(echo "$TAGP" | grep -c 'href="/artikel/ruang-kelas"')"
check "tag page excludes others" "$(echo "$TAGP" | grep -c 'href="/artikel/coding"')" "0"
check "unknown tag 404" "$(code "$B/tag/tidak-ada")" "404"
SRCH=$(curl -s "$B/?q=kenangan")
some "search finds article" "$(echo "$SRCH" | grep -c 'href="/artikel/memory"')"
check "search excludes others" "$(echo "$SRCH" | grep -c 'href="/artikel/coding"')" "0"
check "search page noindex" "$(echo "$SRCH" | grep -c 'name="robots" content="noindex')" "1"
check "search % is literal" "$(curl -s "$B/?q=%25" | grep -c 'Belum ada artikel yang cocok')" "1"
check "search escapes query" "$(curl -s "$B/?q=%3Cscript%3Ex%3C%2Fscript%3E" | grep -c '<script>x</script>')" "0"
check "sitemap 200" "$(code "$B/sitemap.xml")" "200"
check "sitemap has article" "$(curl -s "$B/sitemap.xml" | grep -c '/artikel/coding</loc>')" "1"
check "robots has sitemap" "$(curl -s "$B/robots.txt" | grep -c '^Sitemap: http.*/sitemap.xml')" "1"

TA=$(token "$B/admin.php?page=article")
# draft dengan tag (termasuk tag berisi HTML) dan slug buatan sendiri yang berbahaya
curl -s -o /dev/null -b "$J" -c "$J" -F "csrf_token=$TA" -F 'simpan=1' --form-string 'judul=Draft Rahasia' --form-string 'isi=<p>isi rahasia draft</p>' \
  -F 'status=draft' --form-string 'tags=PHP, <img src=x onerror=alert(1)>, Rahasia' --form-string 'slug=../../Etc <script>' "$B/admin.php?page=article"
DID=$(SQL "SELECT id FROM article WHERE judul='Draft Rahasia'")
DSLUG=$(SQL "SELECT slug FROM article WHERE id=$DID")
check "custom slug sanitized" "$DSLUG" "etc-script"
check "draft has 3 tags" "$(SQL "SELECT COUNT(*) FROM article_tag WHERE article_id=$DID")" "3"
check "draft hidden from guest" "$(code "$B/artikel/$DSLUG")" "404"
check "draft not on home" "$(curl -s "$B/" | grep -c 'Draft Rahasia')" "0"
check "draft not in sitemap" "$(curl -s "$B/sitemap.xml" | grep -c "$DSLUG")" "0"
check "draft hidden in api" "$(code "$B/api/get_article.php?id=$DID")" "404"
check "draft hidden from ai summary" "$(code -H 'Content-Type: application/json' -d "{\"mode\":\"generate_summary\",\"id\":$DID}" "$B/ai_helper.php")" "404"
check "draft tag not public" "$(curl -s "$B/" | grep -c '#Rahasia')" "0"
check "admin can preview draft" "$(curl -s -b "$J" "$B/artikel/$DSLUG" | grep -c 'Pratinjau admin')" "1"
check "preview is noindex" "$(curl -s -b "$J" "$B/artikel/$DSLUG" | grep -c 'content="noindex, nofollow"')" "1"
check "tag html escaped in admin" "$(curl -s -b "$J" -d 'hlm=1' "$B/article_data.php" | grep -c '<img src=x onerror')" "0"

# terbitkan draft lewat edit -> terlihat publik, tag ikut tampil (tetap ter-escape)
curl -s -o /dev/null -b "$J" -c "$J" -F "csrf_token=$TA" -F 'simpan=1' -F "id=$DID" --form-string 'judul=Draft Rahasia' --form-string 'isi=<p>isi rahasia draft</p>' \
  -F 'status=published' --form-string 'tags=PHP, <img src=x onerror=alert(1)>' --form-string "slug=$DSLUG" "$B/admin.php?page=article"
check "published after edit" "$(code "$B/artikel/$DSLUG")" "200"
check "tags updated on edit" "$(SQL "SELECT COUNT(*) FROM article_tag WHERE article_id=$DID")" "2"
check "unused tag removed" "$(SQL "SELECT COUNT(*) FROM tag WHERE slug='rahasia'")" "0"
check "tag html escaped on home" "$(curl -s "$B/" | grep -c '<img src=x onerror')" "0"
check "published_at set" "$(SQL "SELECT CASE WHEN published_at IS NOT NULL THEN 1 ELSE 0 END FROM article WHERE id=$DID")" "1"

# terjadwal di masa depan
curl -s -o /dev/null -b "$J" -c "$J" -F "csrf_token=$TA" -F 'simpan=1' --form-string 'judul=Coding' --form-string 'isi=<p>nanti</p>' \
  -F 'status=published' -F 'published_at=2099-01-01T08:00' "$B/admin.php?page=article"
SID=$(SQL "SELECT MAX(id) FROM article WHERE judul='Coding'")
check "duplicate title gets unique slug" "$(SQL "SELECT slug FROM article WHERE id=$SID")" "coding-2"
check "scheduled hidden from guest" "$(code "$B/artikel/coding-2")" "404"
check "scheduled preview label" "$(curl -s -b "$J" "$B/artikel/coding-2" | grep -c 'terjadwal')" "1"
check "admin table shows scheduled badge" "$(curl -s -b "$J" -d 'hlm=1' "$B/article_data.php" | grep -c 'Terjadwal')" "1"
TS=$(token "$B/admin.php?page=article")
curl -s -o /dev/null -b "$J" -c "$J" -d "csrf_token=$TS&hapus=hapus&id=$SID" "$B/admin.php?page=article"
curl -s -o /dev/null -b "$J" -c "$J" -d "csrf_token=$TS&hapus=hapus&id=$DID" "$B/admin.php?page=article"
check "article delete cascades tags" "$(SQL "SELECT COUNT(*) FROM article_tag WHERE article_id IN ($SID, $DID)")" "0"

echo "== AI admin mode =="
check "ai admin without csrf header 403" "$(code -b "$J" -H 'Content-Type: application/json' -d '{"mode":"generate_caption","text":"x"}' "$B/ai_helper.php")" "403"
check "ai unknown mode 400" "$(code -b "$J" -H "X-CSRF-Token: $T" -H 'Content-Type: application/json' -d '{"mode":"x","text":"x"}' "$B/ai_helper.php")" "400"
r=$(curl -s -b "$J" -H "X-CSRF-Token: $T" -H 'Content-Type: application/json' -d '{"mode":"generate_caption","text":"isi","title":"judul"}' "$B/ai_helper.php")
# lolos login + CSRF dan sampai ke pemanggilan AI (hasilnya tergantung key/jaringan, jadi result atau error sama-sama OK)
case "$r" in *login*|*CSRF*) bad "ai caption passes auth" "$r";; *'"result"'*|*'"error"'*) ok "ai caption passes auth ($(echo "$r" | cut -c1-60))";; *) bad "ai caption passes auth" "$r";; esac

echo "== Password change =="
TP=$(token "$B/admin.php?page=password")
curl -s -o /dev/null -b "$J" -d "csrf_token=$TP&ganti_password=1&password_lama=123456&password_baru=RahasiaBaru99&password_ulang=RahasiaBaru99" "$B/admin.php?page=password"
php -r 'exit(password_verify("RahasiaBaru99", $argv[1]) ? 0 : 1);' "$(SQL "SELECT password FROM $UT WHERE username='admin'")" && ok "password changed" || bad "password changed" "verify failed"

echo "== Logout =="
curl -s -o /dev/null -b "$J" -c "$J" "$B/logout.php"
check "after logout admin redirects" "$(code -b "$J" "$B/admin.php")" "302"

echo "== Login lockout =="
rm -f "$J"
for i in 1 2 3 4 5; do
  t=$(token "$B/login.php")
  curl -s -o /dev/null -b "$J" -c "$J" --data-urlencode "csrf_token=$t" -d 'user=admin&pass=salah' "$B/login.php"
done
t=$(token "$B/login.php")
r=$(curl -s -o /dev/null -w '%{redirect_url}' -b "$J" -c "$J" --data-urlencode "csrf_token=$t" -d 'user=admin&pass=RahasiaBaru99' "$B/login.php")
case "$r" in *login.php?locked=*) ok "6th attempt locked even with correct password";; *) bad "6th attempt locked" "$r";; esac
check "lockout message shown" "$(curl -s "$B/login.php?locked=15" | grep -c 'Terlalu banyak percobaan login')" "1"
check "admin still not logged in" "$(code -b "$J" "$B/admin.php")" "302"
SQL "DELETE FROM login_attempts" >/dev/null

echo
echo "RESULT: $PASS passed, $FAIL failed"
[ "$FAIL" -eq 0 ]
