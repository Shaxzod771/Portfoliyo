# Shaxzod Isomiddinov — Portfolio

Shaxsiy portfolio sayti va uning admin paneli.

- **Sayt** — Vue 3 + Vite + Pinia, uch tilda (UZ / EN / RU)
- **Admin panel** — Vue 3 + Vue Router (`admin.html`)
- **Backend** — PHP 8.3 + MySQL, REST API (`backend/`), Composer shart emas

Jonli sayt: https://shaxzod771.github.io/Portfoliyo/

```
├── index.html, src/          sayt
├── admin.html, src/admin/    admin panel
└── backend/
    ├── .osp/project.ini      OSPanel sozlamasi
    ├── config.example.php    → config.php (bazaga ulanish, email)
    ├── database/schema.sql   phpMyAdmin orqali import qilinadi
    ├── bin/create-admin.php  admin yaratish / parolni tiklash
    ├── public/               veb-ildiz (index.php, yuklangan rasmlar)
    └── src/                  API kodi
```

## 1. Backend (OSPanel 6 + phpMyAdmin)

1. **Loyihani OSPanel'ga ulang.** OSPanel loyihalarni `D:\OSPanel\home` ichidan o'qiydi.
   Papkani ko'chirmaslik uchun havola (junction) yarating — `cmd` da:

   ```bat
   mklink /J D:\OSPanel\home\api.partfoliyo.local C:\Users\Hp\Desktop\Loyihalar\Partfoliyo\backend
   ```

   `backend/.osp/project.ini` domen (`api.partfoliyo.local`), PHP 8.3 va `public/` papkani belgilaydi.
   OSPanel'ni qayta ishga tushiring va **MySQL-8.0** modulini yoqing.

2. **Bazani yarating.** phpMyAdmin → **Import** → `backend/database/schema.sql` → **Import**.
   `partfoliyo` bazasi, jadvallar va hozirgi 5 ta loyiha yaratiladi. Faylni qayta import qilish xavfsiz.
   Agar `schema.sql` ni avvalroq (2FA'dan oldin) import qilgan bo'lsangiz, `backend/database/upgrade-2026-09-auth.sql` ni ham import qiling.

3. **Sozlamalar.** `backend/config.example.php` dan `backend/config.php` nusxasini oling va to'ldiring:
   - `db` — phpMyAdmin'ga qaysi server/login bilan kirsangiz, o'sha. OSPanel 6 da host = modul nomi (`MySQL-8.0`).
   - `mail.password` — Gmail **App Password** (oddiy parol emas): Google akkaunt → 2 bosqichli tekshiruvni yoqing →
     https://myaccount.google.com/apppasswords → yangi parol yarating va shu yerga yozing.
     Bo'sh qolsa, xabarlar faqat bazaga yoziladi.

4. Tekshirish: http://api.partfoliyo.local/api/health → `{"status":"ok","db":true}`

5. **Admin akkaunti.** Admin panelni birinchi marta ochganingizda **"Birinchi sozlash"** sahifasi chiqadi:
   login va parol o'ylab topasiz — shu akkaunt admin bo'ladi. Sahifa faqat bir marta, admin yo'q paytda ishlaydi
   va faqat shu kompyuterdan (localhost) ochiladi. Boshqa joydan sozlash kerak bo'lsa, `config.php` → `setup_key`
   ga uzun tasodifiy qator yozing va sahifada kiriting.

   Parolni unutsangiz yoki telefon (2FA) yo'qolsa — terminalda:

   ```bat
   D:\OSPanel\modules\PHP-8.3\PHP\php.exe backend\bin\create-admin.php shaxzod               :: yangi parol
   D:\OSPanel\modules\PHP-8.3\PHP\php.exe backend\bin\create-admin.php shaxzod --disable-2fa :: 2FA ni o'chirish
   ```

### Kirish xavfsizligi

- Parol: kamida 10 belgi, harf + raqam, loginni o'z ichiga olmaydi, keng tarqalgan parollar rad etiladi
- Har 5 ta noto'g'ri urinishdan keyin login bloklanadi, har safar uzoqroq: 15 daqiqa → 1 soat → 6 soat → 24 soat.
  Bitta IP'dan 15 daqiqada 20 tadan ortiq urinish ham bloklanadi. Kirish sahifasida qolgan urinishlar ko'rinadi
- Mavjud bo'lmagan login ham xuddi shunday javob oladi — qaysi login borligini bilib bo'lmaydi
- Ixtiyoriy **2FA** (Google Authenticator / Authy): Profil → "2FA ni yoqish"
- Sessiya: oddiy kirish 12 soat va 2 soat faolsizlikdan keyin yopiladi; "Meni eslab qol" — 30 kun
- Profil sahifasida faol sessiyalar (boshqa qurilmani chiqarib yuborish mumkin) va xavfsizlik tarixi
- Parol o'zgarganda boshqa barcha sessiyalar yopiladi; tokenlar bazada faqat SHA-256 hash ko'rinishida saqlanadi

## 2. Frontend

```sh
npm install
cp .env.example .env.local     # VITE_API_URL=http://api.partfoliyo.local
npm run dev
```

- Sayt: http://localhost:5173/Portfoliyo/
- Admin panel: http://localhost:5173/Portfoliyo/admin.html

`backend/config.php` dagi `cors_origins` ro'yxatida frontend manzili bo'lishi kerak (localhost:5173 allaqachon bor).

### Admin panelda

- **Dashboard** — loyihalar va xabarlar statistikasi, oxirgi xabarlar
- **Loyihalar** — qo'shish, tahrirlash (3 tilda), rasm yuklash, tartibini o'zgartirish, yashirish, o'chirish.
  Kartochkada **rasm** yoki **jonli sayt** (demo havola kartaning ichida ochilib turadi) ko'rsatishni tanlash mumkin
- **Xabarlar** — kontakt formasidan kelganlar: qidiruv, filtr, o'qilgan/o'qilmagan, javob yozish, o'chirish
- **Profil** — parol, 2FA, faol sessiyalar, xavfsizlik tarixi

## Kontakt formasi → email

Xabarlar **isomiddinovshaxzod771@gmail.com** ga keladi:

| Holat | Qanday ishlaydi |
| --- | --- |
| `VITE_API_URL` bor | Backend xabarni bazaga yozadi (admin panelda ko'rinadi) va SMTP orqali emailga yuboradi |
| `VITE_API_URL` yo'q (GitHub Pages) | Forma [FormSubmit](https://formsubmit.co) orqali to'g'ridan-to'g'ri emailga yuboradi |

> FormSubmit birinchi xabarda emailingizga **tasdiqlash xati** yuboradi — undagi "Activate" tugmasini bir marta bosing.

Email manzili `src/constants/site.js` (sayt) va `backend/config.php` → `mail.to` (backend) da.

## Ma'lumotlarni o'zgartirish

- Email, telefon, ijtimoiy tarmoqlar — `src/constants/site.js`
- Loyihalar — admin panel orqali (backend bo'lmasa: `src/constants/projects.js`)
- Barcha matnlar va tarjimalar — `src/constants/translations.js`

## Deploy

`main` branchga push qilinganda GitHub Actions saytni build qiladi va `gh-pages` branchga joylaydi.
GitHub → Settings → Pages → Source: **Deploy from a branch → `gh-pages` / (root)** bo'lishi kerak.

GitHub Pages faqat statik fayllarni joylaydi — PHP backend u yerda ishlamaydi. Backendni internetga
chiqarsangiz (PHP hosting), uning manzilini repository **Variables** bo'limida `VITE_API_URL` deb qo'shing
va `config.php` → `cors_origins` ga `https://shaxzod771.github.io` borligini tekshiring.
