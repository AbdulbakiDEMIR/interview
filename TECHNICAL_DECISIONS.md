## Teknik Dokümantasyon ve Mimari Kararlar

Bu projede harici API entegrasyonu, kod kalitesi, güvenlik, hata yönetimi ve sürdürülebilirlik ilkelerine uygun olarak aşağıdaki teknik mimari kararlar alınmış ve uygulanmıştır:

### 1. Statik IP ve Nginx Reverse Proxy Mimarisi (Google Cloud VM)
* **Problem ve Gerekçe:** Yerel geliştirme ortamında sabit (statik) IP adresinin bulunmaması, dinamik IP değişiklikleri ve olası elektrik/internet kesintileri nedeniyle Turkpin API IP Whitelist erişim kısıtlamasına takılma riski bulunmaktaydı.
* **Çözüm:** Google Cloud Platform (GCP) üzerinde ayrılmış statik IP adresine sahip bir Sanal Sunucu (VM) yapılandırılmış ve Turkpin Whitelist sistemine bu statik IP adresi tanımlanmıştır.
* **Nginx Reverse Proxy Yapılandırması:** Sunucu üzerinde Nginx reverse proxy kurularak isteklerin güvenli bir şekilde iletilmesi sağlanmıştır. Güvenlik amacıyla proxy seviyesinde IP kısıtlaması eklenmiş ve SSL SNI desteği aktif edilmiştir.

```nginx
server {
    listen 80;
    server_name _;

    location / {
        # IP Kısıtlama
        allow <CLIENT_IP>;
        deny all;

        # Proxy ve SNI Ayarı
        proxy_pass <TURKPIN_API_URL>;
        proxy_set_header Host <turkpin>;
        proxy_ssl_server_name on;
    }
}
```

### 2. Konteynerleştirme ve Ortam İzolasyonu (Docker & Docker Compose)
* **Gerekçe & Amaca Uygunluk:** Projenin işletim sisteminden bağımsız, PHP sürüm veya eklenti uyuşmazlıkları (PHP 8.4) yaşamadan ve yerel web sunucusu konfigürasyonlarıyla uğraşmadan tek komutla çalıştırılabilmesi hedeflenmiştir.
* **Dockerfile Özellikleri:**
  * `php:8.4-apache` resmi imajı taban alındı.
  * Routing (Bramus Router) mekanizmasının sorunsuz çalışması için Apache `mod_rewrite` modülü aktif edildi.
  * Bağımlılık yönetimi için Composer aracı resmi imajdan kopyalanarak ortama dahil edildi.
  * Smarty template engine önbellekleme klasörü (`templates_c`) için gerekli yazma izinleri (`775` / `www-data`) otomatik tanımlandı.
* **Docker Compose Yapılandırması (`docker-compose.yml`):**
  * Uygulama `8081:80` port yönlendirmesiyle izole şekilde erişilebilir kılındı.
  * Canlı kod geliştirmeyi desteklemek için volume bağlama (`.:/var/www/html`) yapıldı.
  * `.env` dosyası konteyner çalışma ortamına aktarılarak API anahtarlarının güvenliği sağlandı.

### 3. Mevcut Şablon ve Kod Hatalarının Tespiti ve Düzeltilmesi
Projenin başlangıç şablonunda tespit edilen ve sistem hatalarına yol açan kritik eksiklikler giderilmiştir:
* **Şablon Yapısı ve Yönlendirme Refaktörü (`src/templates/products.html` & `src/classes/Main.php`):** Jenerik isimli `home.html` dosyası silinerek içeriğe uygun `products.html` yapısına geçildi ve Bramus Router yönlendirme mantığı buna göre refactor edildi.
* **Özel 404 Sayfası Desteği (`src/templates/404.html`):** Tanımsız rotalara yapılan erişimlerde kullanıcının anlamlı bir hata ekranıyla karşılaşması için özel `404.html` şablonu oluşturuldu ve router 404 işleyicisine (`set404`) bağlandı.
* **Smarty Derleme Dizini Uyumsuzluğu (`src/classes/Main.php`):** Orijinal koddaki sabit `/tmp` dizini kullanımı Windows ve farklı sunucu ortamlarında yetki/bulunamadı (Permission / Directory Not Found) hataları üretiyordu. Proje dizini altında çapraz platform uyumlu `__DIR__ . '/../../templates_c'` yolu ile değiştirildi.
* **Session Dil Anahtarı Tanımsızlık Hatası (`src/classes/Main.php`):** `$_SESSION['lang']` değişkeni ilk sayfa yüklemesinde bulunmadığında oluşan PHP uyarısı/hatası, Null Coalescing (`??`) operatörü ile güvenli hale getirildi: `$lang = $_SESSION['lang'] ?? 'tr';`.
* **İngilizce Dil Dosyası Eksiklikleri (`src/languages/en.php`):** Orijinal `en.php` dosyasındaki eksik çeviri anahtarları `tr.php` ile tam uyumlu ve senkronize olacak şekilde tamamlandı.

### 4. Mimari Yapı ve İzolasyon (Encapsulation)
* **TurkpinApiClient (`src/classes/TurkpinApiClient.php`):** Turkpin API ile olan tüm haberleşme tek bir servis sınıfında toplandı. İstek XML payload'unun oluşturulması, cURL HTTP çağrısı, XML parse adımları ve yanıt doğrulama (`HATA_NO` / `error` kontrolleri) modüler metodlara ayrıldı.
* **API Zaman Aşımı ve Log Optimizasyonu:** Geniş tarih aralıklarında Turkpin API'den dönen devasa XML (3.4 MB+) yanıtlarının zaman aşımına takılmaması için cURL `CURLOPT_TIMEOUT` süresi 90 saniyeye çıkarıldı ve log I/O performansını korumak için loglanan yanıt boyutu 2000 karakterle sınırlandırıldı.
* **Ortam Değişkenleri Yönetimi (`.env`):** Hassas API erişim bilgileri (`TURKPIN_API_URL`, `TURKPIN_API_USERNAME`, `TURKPIN_API_PASSWORD`) doğrudan koda yazılmak yerine `.env` dosyasında kapsüllendi.

### 5. Merkezi Hata Yönetimi (Exception Handling)
* **Özel Exception Sınıfları:** API ve doğrulama hatalarını ayırt edebilmek amacıyla `TurkpinApiException` ve `ValidationException` sınıfları oluşturuldu.
* **ExceptionHandler (`src/Handlers/ExceptionHandler.php`):** Tüm istemci yanıtları standart JSON formatına getirildi (`success`, `message`). Beklenmeyen kritik hatalar loglanırken kullanıcıya güvenli hata mesajları dönülmesi sağlandı.

### 6. Loglama Mekanizması (`src/Services/Logger.php`)
* **Hata ve İstek Takibi:** Turkpin API istek ve yanıt detayları `logs/turkpin_api.log` dosyasına, uygulama içi hatalar ise `logs/app.log` dosyasına zaman damgası ve JSON bağlam (context) verisiyle kaydedilir.

### 7. Çift Taraflı Form Doğrulaması ve Güvenlik
* **İstemci Tarafı (Frontend):** Form verileri gönderilmeden önce istemci tarafında doğrulandı. Çift sipariş (duplicate submission) gönderimini engellemek için buton kilitleme mekanizması kuruldu.
* **Sunucu Tarafı (Backend):** `Order` ve `Product` sınıflarında tüm girdiler veri tipine göre doğrulandı ve sanitize edildi.

### 8. Çoklu Dil Desteği (i18n)
* **Dinamik Çeviri:** Türkçe (`src/languages/tr.php`) ve İngilizce (`src/languages/en.php`) dil yapıları korundu, API yanıt mesajları ve sistem hataları dil desteğine uyumlu hale getirildi.

### 9. Otomatik Test ve Kod Kalitesi Standartları
* **Unit & Integration Testleri:** PHPUnit ile API yanıt kurguları, sipariş oluşturma süreçleri, ürün ve oyun listesi metodları için test senaryoları yazıldı (`tests/`).
* **Kod Standartları:** PSR-12 standartlarına uyum için `PHP-CS-Fixer` yapılandırması sağlandı.