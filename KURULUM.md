# Lusso Home Concept — kurulum notları

Eski lussohomeconcept.com (WordPress + WooCommerce, Basel teması) sitesinin
Laravel 13 + Filament 5 ile yeniden yazımı. Tasarım eski sitenin ölçüleriyle
birebir; kod sıfırdan.

## Yerelde çalıştırma

```
composer install
cp .env.example .env && php artisan key:generate   # DB: MySQL "lusso"
php artisan migrate --seed                          # yönetici + sayfalar + slider
php artisan lusso:aktar --liste                     # kategori + 2159 ürün (görselsiz, listeye yazar)
bash tools/gorsel-indir.sh 8                        # görselleri curl ile paralel indirir
php -S 127.0.0.1:8191 -t public server.php
```

- Vitrin: http://127.0.0.1:8191 · Panel: http://127.0.0.1:8191/admin
- Yerel yönetici bilgileri `.env` içinde (`ADMIN_EMAIL` / `ADMIN_PASSWORD`).
- Testler: `php artisan test`

## Ürün aktarımı (`php artisan lusso:aktar`)

- Kaynak: eski sitenin açık WooCommerce Store API'si (`/wp-json/wc/store/v1`).
- Tekrar çalıştırılabilir: kayıtlar `wc_id` ile eşleşir, var olan görsel yeniden indirilmez.
- `--liste`: görseli indirmez, `storage/app/gorsel-listesi.txt`'ye yazar. **Önerilen yol** —
  eski sunucuda PHP'nin eşzamanlı indirmesi her istekte 60 sn takılıyordu.
- `--baslangic=N --bitis=M`: sayfa aralığı (sayfa başı 50 ürün).
- Eski site canlı ve yavaş: **paralel aktarım işi çalıştırmayın**, sunucuyu kilitliyor.
- Fiyatı 0 olan ürünler (eski sitede de öyle) "Devamını oku" gösterir, sepete eklenemez;
  ürün sayfasında WhatsApp'tan fiyat sorma bağlantısı çıkar.

## Müşteri ve sipariş aktarımı (`php artisan lusso:aktar-siparis`)

- Kaynak: `storage/app/woo/customers.json` ve `orders.json` (git'e girmez, kişisel veri).
  Dökümler eski WP panelinde oturum açıkken tarayıcıdan salt okunur REST ile alındı
  (`/wp-json/wc/v3/customers?role=all`, `/wp-json/wc/v3/orders?status=any`, alanlar kısaltılmış).
- Tekrar çalıştırılabilir: üye ve sipariş `wc_id` ile eşleşir; `--deneme` yalnız sayar.
- Eski şifreler REST'ten alınamaz: üyeler "Şifremi unuttum" ile yeni şifre belirler.
- Personel hesapları (yönetici, mağaza müdürü) aktarılmaz; panel yetkisi elle verilir.
- Sipariş numarası eskisiyle aynı (ör. 10847), kaynak `eski`. Tamamlanan = ödendi
  (ödeme tarihi korunur), iptal = ödenmedi. Paynet ödemeleri "Kredi Kartı (Paynet, eski site)".
- Ürün eşleşmesi: `wc_id`, yoksa stok kodu, yoksa birebir ad; eski sitede silinmiş ürünlerin
  kalemleri ad ve fiyatla, ürün bağlantısı olmadan gelir.
- **Canlıya geçmeden hemen önce** dökümleri yenileyip komutu bir kez daha çalıştırın
  (son siparişler gelsin).

## Ödeme

- **Tiko** (3D Secure): kart formu müşterinin tarayıcısından doğrudan Tiko'ya gider,
  kart bilgisi sunucumuza uğramaz. Dönüşte sonuca güvenilmez; `payment/status` sorgusu
  ve imza ile doğrulanır. Bildirim adresi: `/tiko/bildirim` (Tiko'ya bildirilecek).
  Kimlik bilgileri panelde: Mağaza Ayarları → Ödeme.
- **Havale/EFT**: banka bilgileri panelden girilir; ödeme gelince siparişte "Ödendi".
- **Kapıda ödeme**.
- `TIKO_ALLOW_SIMULATION=true` yalnız yerelde: Tiko bilgisi yokken sahte onay ekranı.

## Turhost / cPanel

- Tek klasör kurulumu: kök `.htaccess` kaynak klasörleri kapatır, istekleri `public/`'e yönlendirir.
- Görseller `public/medya` altında (`storage:link` gerekmez). Şu an `.gitignore`'da —
  yayına çıkarken nasıl taşınacağına karar verilecek (~5000 dosya).
- Filament varlıkları (`public/css|js|fonts/filament`) depoda; sunucuda Node/terminal gerekmez.

## Eski adresler korunuyor

`/product/…`, `/product-category/…/…`, `/product-tag/…`, `/cart`, `/hesabim`,
`/begendiklerim`, `/iletisim`, `/hakkimizda`, `/teslimat-kargo` … ve eski arama
`/?s=…` → `/arama?s=…` yönlendirmesi.
