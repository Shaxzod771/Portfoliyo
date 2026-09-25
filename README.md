# Shaxzod Isomiddinov — Portfolio

Shaxsiy portfolio sayti: Vue 3 + Vite + Pinia. Uch tilda (UZ / EN / RU), kontakt formasi xabarlarni Telegram botga yuboradi.

Jonli sayt: https://shaxzod-hp.github.io/Partfoliyo/

## Ishga tushirish

```sh
npm install
cp .env.example .env.local   # keyin bot token va chat id ni yozing
npm run dev
```

Production build va uni lokal ko'rish:

```sh
npm run build
npm run preview
```

## Kontakt formasi (Telegram)

| O'zgaruvchi         | Nima                                             |
| ------------------- | ------------------------------------------------ |
| `VITE_TG_BOT_TOKEN` | @BotFather bergan bot tokeni                      |
| `VITE_TG_CHAT_ID`   | Xabarlar keladigan chat (sizning Telegram id'ingiz) |

Lokal ishlash uchun `.env.local` faylida, GitHub Actions uchun esa repository **Secrets** bo'limida saqlanadi.
`.env.local` gitga tushmaydi.

## Ma'lumotlarni o'zgartirish

- Email, telefon, ijtimoiy tarmoqlar — `src/constants/site.js`
- Loyihalar (rasm, GitHub va jonli havolalar) — `src/constants/projects.js`
- Barcha matnlar va tarjimalar — `src/constants/translations.js`

## Deploy

`main` branchga push qilinganda GitHub Actions saytni build qiladi va `gh-pages` branchga joylaydi.
GitHub → Settings → Pages → Source: **Deploy from a branch → `gh-pages` / (root)** bo'lishi kerak.
